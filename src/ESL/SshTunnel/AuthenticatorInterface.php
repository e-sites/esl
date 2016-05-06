<?php
/**
 * Key authentication
 *
 * @package SshTunnel
 * @version $Id$
 */
interface ESL_SshTunnel_AuthenticatorInterface
{
	/**
	 * 
	 * @param resource $session An SSH connection link identifier, obtained from a call to ssh2_connect()
	 * @return null
	 */
	public function login($session);

	/**
	 * Arguments to use for authentication when invoking ssh from commandline
	 *
	 * @return array
	 */
	public function commandlineArgs();
}
?>