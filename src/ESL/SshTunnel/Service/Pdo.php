<?php
/**
 * PDO connected via ssh tunnel to another host
 *
 * Currently connects to port 3306 by default
 *
 * @package SshTunnel
 * @version $Id$
 */
class ESL_SshTunnel_Service_Pdo extends Pdo
{
	/**
	 * @var ESL_SshTunnel
	 */
	private $sshTunnel;

	/**
	 * @see \PDO::__construct()
	 *
	 * @param ESL_SshTunnel $sshTunnel
	 * @param string $dsn The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @param string $username The user name for the DSN string. This parameter is optional for some PDO drivers.
	 * @param string $password The password for the DSN string. This parameter is optional for some PDO drivers.
	 * @param array $options A key=>value array of driver-specific connection options.
	 */
	public function __construct(ESL_SshTunnel $sshTunnel, $dsn, $username = null, $password = null, array $options = null)
	{
		$this->sshTunnel = $sshTunnel;

		// Parse DSN string and determine host and port
		$dsnConfig = $this->parseDsnString($dsn);
		$dsnPrefix = key($dsnConfig);

		if (!empty($dsnConfig[$dsnPrefix]['socket'])) {
			throw new \InvalidArgumentException('Can not tunnel a socket over SSH');
		}

		// Where to connect tunnel to
		$remoteHost = (isset($dsnConfig[$dsnPrefix]['host']) && $dsnConfig[$dsnPrefix]['host'] != 'localhost') ? $dsnConfig[$dsnPrefix]['host'] : '127.0.0.1';
		$remotePort = (isset($dsnConfig[$dsnPrefix]['port'])) ? $dsnConfig[$dsnPrefix]['port'] : $this->getPortnumber($dsnPrefix);

		// Update DSN to connect to forwarded port
		$dsnConfig[$dsnPrefix]['host'] = '127.0.0.1';
		$dsnConfig[$dsnPrefix]['port'] = $sshTunnel->forwardTcpPort($remotePort, $remoteHost);

		// Rebuold DSN-string
		$dsn = $this->buildDsnString($dsnConfig);

		parent::__construct($dsn, $username, $password, $options);
	}

	/**
	 * Parse DSN-string into assosiative array
	 *
	 * @param string $dsn DSN (ex; mysql:host=127.0.0.1;port=1234;dbname=test)
	 * @return array
	 */
	protected function parseDsnString($dsn)
	{
		if (false === ($splitAt = strpos($dsn, ':'))) {
			throw new \InvalidArgumentException("Invalid DSN string; missing prefix in '$dsn'.");
		}

		$prefix = substr($dsn, 0, $splitAt);
		$parameters = substr($dsn, $splitAt + 1);

		$config = $matches = array();
		if (preg_match_all('/([^=]+)=([^;]+);?/', $parameters, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$config[$match[1]] = $match[2];
			}
		}

		return array($prefix => $config);
	}

	/**
	 * Builds a DSN-string from a config array with 1 element, the key being the prefix, and its values being the parameters
	 *
	 * @param array $dsnConfig
	 * @return string (ex; mysql:host=127.0.0.1;port=1234;dbname=test)
	 */
	protected function buildDsnString(array $dsnConfig)
	{
		$prefix = key($dsnConfig);

		$parameters = array();
		foreach ($dsnConfig[$prefix] as $element => $value) {
			$parameters[] = "$element=$value";
		}

		return $prefix . ':' . implode(';', $parameters);
	}

	/**
	 * @todo Not implemented
	 *
	 * @param string $dsnPrefix
	 * @return int
	 */
	protected function getPortnumber($dsnPrefix)
	{
		// @todo Port 3306 is the default for Mysql. Should match the $dsnPrefix
		return 3306;
	}
}
?>