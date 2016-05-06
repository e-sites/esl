<?php
/**
 * PDO connected via ssh tunnel to another host
 *
 * Currently connects to port 3306 by default
 *
 * @package SshTunnel
 * @version $Id$
 */
class ESL_SshTunnel_Service_SplFileInfo extends SplFileInfo
{
	/**
	 * @var ESL_SshTunnel
	 */
	private $sshTunnel;

	/**
	 * @see \SplFileInfo::__construct()
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param ESL_SshTunnel $sshTunnel
	 * @param string $filename Path to the file
	 */
	public function __construct(ESL_SshTunnel $sshTunnel, $filename)
	{
		$this->sshTunnel = $sshTunnel;
		parent::__construct($sshTunnel->sftp($filename));
	}
}
?>