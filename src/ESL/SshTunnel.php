<?php
/**
 * SSH tunnel to another host.
 *
 * Requires the ssh2 pecl package
 * @link http://php.net/manual/en/book.ssh2.php

 * @package SshTunnel
 * @version $Id: SshTunnel.php 909 2015-07-10 12:07:32Z fpruis $
 */
class ESL_SshTunnel
{
	/**
	 * Hostname
	 *
	 * @var string
	 */
	private $host;

	/**
	 * Authentication module
	 *
	 * @var ESL_SshTunnel_AuthenticatorInterface
	 */
	private $authenticator;

	/**
	 * @var bool
	 */
	private $isConnected = false;

	/**
	 * Resource from ssh2_connect()
	 *
	 * @var resource
	 */
	private $ssh;

	/**
	 * Resource from ssh2_sftp()
	 *
	 * @var resource
	 */
	private $sftp;

	/**
	 * @var resource[]
	 */
	private $spawnedProcesses = array();

	/**
	 * @var int[]
	 */
	private $portForwards = array();

	/**
	 *
	 * @param string $host Hostname
	 * @param string $username Username
	 * @param string $pubkeyfile Path to public key file
	 * @param string $privkeyfile Path to private key file
	 * @return $this
	 */
	static public function connectUsingKeyAuthentication($host, $username, $pubkeyfile, $privkeyfile)
	{
		$authenticator = new ESL_SshTunnel_Authenticator_KeyFile($username, $pubkeyfile, $privkeyfile);

		return new static($host, $authenticator);
	}

	/**
	 *
	 * @param string $host Hostname
	 * @param string $username Username
	 * @param string $password Password
	 * @return $this
	 */
	static public function connectUsingPasswordAuthentication($host, $username, $password)
	{
		$authenticator = new ESL_SshTunnel_Authenticator_Password($username, $password);

		return new static($host, $authenticator);
	}

	/**
	 *
	 * @param string $host Hostname
	 * @param ESL_SshTunnel_AuthenticatorInterface $authenticator
	 */
	private function __construct($host, ESL_SshTunnel_AuthenticatorInterface $authenticator)
	{
		if (!function_exists('ssh2_connect')) {
			trigger_error('ESL_SshTunnel requires the ssh2 pecl extension.', E_USER_ERROR);
		}

		if (!is_string($host)) {
			throw new \InvalidArgumentException("Host is required to be a string.");
		}

		$this->host = $host;
		$this->authenticator = $authenticator;
	}

	public function __destruct()
	{
		// Terminate all the port forwards we spawned
		foreach ($this->spawnedProcesses as $proc) {
			proc_terminate($proc) && proc_close($proc);
		}

		if ($this->sftp) {
			$this->sftp = null;
		}
	}

	/**
	 * Connect SSH
	 *
	 * @throws \RuntimeException
	 *
	 * @return null
	 */
	protected function connect()
	{
		$host = $this->host;

		// Open SSH connection
		if (!($this->ssh = ssh2_connect($host, 22))) {
			throw new \RuntimeException("Unable to connect to $host");
		}

		try {
			$this->authenticator->login($this->ssh);
		} catch (\RuntimeException $e) {
			throw new \RuntimeException("Unabled to authenticate to host $host", null, $e);
		}

		$this->isConnected = true;
	}

	/**
	 * Forward a tcp port to remote host
	 *
	 * Beware! A forwarded port becomes useless as soon as the ssh tunnel is disconnected. When you forward a port, make sure you preserve
	 * this instance to keep the tunnel open.
	 *
	 * @todo Use ssh2_tunnel() instead of spawning a new ssh process
	 *
	 * @param int $portRemote Remote TCP port number
	 * @param string $hostRemote Remote host name [default='127.0.0.1']
	 * @return int Local TCP port number
	 */
	public function forwardTcpPort($portRemote, $hostRemote = '127.0.0.1')
	{
		// Reuse existing forward
		$cacheIndex = "$hostRemote:$portRemote";
		if (isset($this->portForwards[$cacheIndex])) {
			return $this->portForwards[$cacheIndex];
		}

		// Find an available local port we can use for forwarding
		$portLocal = $this->findAvailableLocalPort();

		// Spawn ssh
		$sshOptions = array(
			'StrictHostKeyChecking' => false,
			'BatchMode' => true,
			'ExitOnForwardFailure' => true,
			'PermitLocalCommand' => true,
			'LocalCommand' => 'echo -n ok'
		);

		// configuration
		$sshCommand = array('ssh');
		foreach ($sshOptions as $option => $value) {
			if (is_bool($value)) {
				$value = ($value ? 'yes' : 'no');
			}
			$sshCommand[] = '-o ' . escapeshellarg("$option $value");
		}
		$sshCommand[] = '-L ' . escapeshellarg("localhost:$portLocal:$hostRemote:$portRemote");
		$sshCommand[] = '-n'; // Redirects stdin from /dev/null
		$sshCommand[] = '-N'; // Do not execute a remote command.

		// authentication
		$sshCommand = array_merge($sshCommand, $this->authenticator->commandlineArgs());
		$sshCommand[] = $this->host;

		$cmd = implode(' ', $sshCommand);
		$descriptorspec = array(
			1 => array('pipe', 'w'), // stdout
			2 => array('pipe', 'w')  // stderr
		);
		$pipes = array();

		if (!($proc = proc_open($cmd, $descriptorspec, $pipes))) {
			throw new \RuntimeException("Failed to spawn SSH process");
		}

		$this->spawnedProcesses[] = $proc;

		// Block until we have read 'ok' from LocalCommand and know the connection is established
		fread($pipes[1], 2);

		// Non-blocking mode for output streams
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$status = proc_get_status($proc);
		if (empty($status['running'])) {
			throw new \RuntimeException(
				"Failed forwarding port $portLocal to $hostRemote:$portRemote on host {$this->host};"
				. PHP_EOL . stream_get_contents($pipes[2]) . PHP_EOL
				. ' in command ' . $cmd
			);
		}

		// Rember for later reusage
		$this->portForwards[$cacheIndex] = $portLocal;

		return $portLocal;
	}

	/**
	 * SFTP subsystem
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $filename
	 * @return string SSH2-SFTP-resource To be used with ssh2.sftp fopen-wrapper
	 */
	public function sftp($filename)
	{
		if (substr($filename, 0, 1) !== '/') {
			throw new InvalidArgumentException("Filename is required to be an absolute path.");
		}
		if (parse_url($filename, PHP_URL_SCHEME)) {
			throw new InvalidArgumentException("Filename '$filename' already contains a fopen-wrapper.");
		}

		if (!$this->sftp) {
			if (!$this->isConnected) {
				$this->connect();
			}
			// Create SFTP-session
			$this->sftp = ssh2_sftp($this->ssh);
		}

		return 'ssh2.sftp://' . (string) $this->sftp . '/' . $filename;
	}

	/**
	 * Return an unused tcp-port number
	 *
	 * @return int
	 */
	protected function findAvailableLocalPort()
	{
		// Find a random, unprivileged, unused TCP port to use for forwarding
		$usedPorts = explode(PHP_EOL, trim(shell_exec('netstat -nat | tail -n+3 | awk \'{print $4}\' | awk -F : \'{print $NF}\' | sort -n | uniq')));
		do {
			$port = mt_rand(1025, 65535);
		} while (in_array($port, $usedPorts));

		return (int) $port;
	}

	/**
	 * Execute an external program on remote host
	 *
	 * @see \exec()
	 *
	 * @param string $command The command that will be executed.
	 * @param &array $output Variable by reference to store command output
	 * @param &int $exitstatus Exit status of executed command
	 * @return string The last line from the result of the command
	 */
	public function exec($command, array &$output = null, &$exitstatus = null)
	{
		if (!$this->isConnected) {
			$this->connect();
		}

		// Get the exit status for the executed command
		$command = "($command); echo -ne \"\n\$?\";";

		if (!($streamOut = ssh2_exec($this->ssh, $command))) {
			throw new \RuntimeException('Failed to execute command');
		}

		stream_set_blocking($streamOut, true);

		while (($meta = stream_get_meta_data($streamOut)) && !$meta['eof'] && false !== ($line = fgets($streamOut))) {
			$output[] = rtrim($line);
		}

		fclose($streamOut);

		// Get the exitcode we added to the command
		$exitstatus = (int) array_pop($output);

		// Strip trailing newline as exec() does
		if (end($output) === '') {
			array_pop($output);
		}

		// Return the last line of output
		return (string) end($output);
	}

	/**
	 * @deprecated
	 * @return ESL_SshTunnel_Service_SplFileInfo
	 */
	public function SplFileInfo($filename)
	{
		return new ESL_SshTunnel_Service_SplFileInfo($this, $filename);
	}

	/**
	 * @deprecated
	 * @return ESL_SshTunnel_Service_Pdo
	 */
	public function PDO($dsn, $username = null, $password = null, array $options = array())
	{
		return new ESL_SshTunnel_Service_Pdo($this, $dsn, $username, $password, $options);
	}
}
?>