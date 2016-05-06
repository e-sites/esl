<?php
/**
 * Password authentication
 *
 * @package SshTunnel
 * @version $Id$
 */
class ESL_SshTunnel_Authenticator_Password implements ESL_SshTunnel_AuthenticatorInterface
{
	/**
	 * Username
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Password
	 *
	 * @var string
	 */
	private $password;

	/**
	 * 
	 * @throws \InvalidArgumentException
	 * 
	 * @param string $username Remote user name
	 * @param string $password Password for username
	 */
	public function __construct($username, $password)
	{
		if (!is_string($username)) {
			throw new \InvalidArgumentException("Username is required to be a string.");
		}
		if (!is_string($password)) {
			throw new \InvalidArgumentException("Password is required to be a string.");
		}

		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @throws \RuntimeException
	 * 
	 * @param resource $session An SSH connection link identifier, obtained from a call to ssh2_connect()
	 */
	public function login($session)
	{
		if (!ssh2_auth_password($session, $this->username, $this->password)) {
			throw new \RuntimeException("Unabled to login as '{$this->username}' using password.");
		}
	}

	/**
	 * Not implemented!
	 *
	 * @access
	 * @internal
	 */
	public function commandlineArgs()
	{
		trigger_error('Command-line arguments are not supported for password authentication', E_USER_ERROR);
	}
}
?>