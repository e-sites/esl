<?php
/**
 * Key authentication
 *
 * @package SshTunnel
 * @version $Id$
 */
class ESL_SshTunnel_Authenticator_KeyFile implements ESL_SshTunnel_AuthenticatorInterface
{
	/**
	 * Username
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Path to public key
	 *
	 * @var string
	 */
	private $pubkeyfile;

	/**
	 * Path to private key
	 *
	 * @var string
	 */
	private $privkeyfile;

	/**
	 * $passphrase
	 *
	 * @var string
	 */
	private $passphrase;

	/**
	 * 
	 * @throws \InvalidArgumentException
	 * 
	 * @param string $username Remote user name
	 * @param string $pubkeyfile The public key file needs to be in OpenSSH's format
	 * @param string $privkeyfile
	 * @param string $passphrase If privkeyfile is encrypted (which it should be), the passphrase must be provided.
	 */
	public function __construct($username, $pubkeyfile, $privkeyfile, $passphrase = '')
	{
		if (!is_string($username)) {
			throw new \InvalidArgumentException("Username is required to be a string.");
		}
		if (!is_readable($pubkeyfile)) {
			throw new \InvalidArgumentException("Public key file '$pubkeyfile' can not be read.");
		}
		if (!is_readable($privkeyfile)) {
			throw new \InvalidArgumentException("Private key file '$privkeyfile' can not be read.");
		}
		if (!is_string($passphrase)) {
			throw new \InvalidArgumentException("Passphrase is required to be a string");
		}

		$this->username = $username;
		$this->pubkeyfile = $pubkeyfile;
		$this->privkeyfile = $privkeyfile;
		$this->passphrase = $passphrase;
	}

	/**
	 * @throws \RuntimeException
	 * 
	 * @param resource $session An SSH connection link identifier, obtained from a call to ssh2_connect()
	 */
	public function login($session)
	{
		if (!ssh2_auth_pubkey_file($session, $this->username, $this->pubkeyfile, $this->privkeyfile, $this->passphrase)) {
			throw new \RuntimeException("Unabled to login as '{$this->username}' using public key authentication.");
		}
	}

	/**
	 * Arguments to use for authentication when invoking ssh from commandline
	 *
	 * @return array
	 */
	public function commandlineArgs()
	{
		return array(
			'-i ' . escapeshellarg($this->privkeyfile), // identity_file
			'-l ' . escapeshellarg($this->username),    // login_name
		);
	}
}
?>