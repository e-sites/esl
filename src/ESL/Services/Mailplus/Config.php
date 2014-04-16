<?php
/**
 * Configuration for ESL_Services_Mailplus
 * 
 * @package Mailplus
 * @version $Id: Config.php 662 2014-02-14 14:17:32Z fpruis $
 */
class ESL_Services_Mailplus_Config
{
	/**
	 *
	 * @var string
	 */
	protected $sWsdlBase = 'http://api.mailplus.nl/ApiService/soap/%s_v2?wsdl';

	/**
	 *
	 * @var string
	 */
	protected $sApiId = null;

	/**
	 *
	 * @var string
	 */
	protected $sApiPassword = null;

	/**
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sApiId API ID of the MailPlus account.
	 * @param string $sApiPassword API password of the MailPlus account.
	 */
	public function __construct($sApiId, $sApiPassword)
	{
		if (empty($sApiId)) {
			throw new InvalidArgumentException('API ID not set in MailPlus config');
		}
		if (empty($sApiPassword)) {
			throw new InvalidArgumentException('API password not set in MailPlus config');
		}

		$this->sApiId = $sApiId;
		$this->sApiPassword = $sApiPassword;
	}

	/**
	 * Returns the location of the WSDL for the requested service
	 *
	 * @param string $sService Service name
	 * @return string
	 */
	public function getLocation($sService)
	{
		return sprintf($this->sWsdlBase, $sService);
	}

	/**
	 * Returns connection options for SoapClient
	 *
	 * @return array
	 */
	public function getConfigOptions()
	{
		return array(
			'cache_wsdl' => WSDL_CACHE_NONE,
			'exceptions' => true
		);
	}

	/**
	 * API ID of the MailPlus account.
	 *
	 * @return string
	 */
	public function getApiId()
	{
		return $this->sApiId;
	}

	/**
	 * API password of the MailPlus account.
	 * 
	 * @return string
	 */
	public function getApiPassword()
	{
		return $this->sApiPassword;
	}
}
?>