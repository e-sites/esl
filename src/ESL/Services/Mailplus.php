<?php
/**
 * MailPlus SOAP API implementation
 *
 * Currently supports subscribing new addresses into the lists
 * 
 * @package Mailplus
 * @version $Id: Mailplus.php 662 2014-02-14 14:17:32Z fpruis $
 */
class ESL_Services_Mailplus
{
	/**
	 *
	 * @var ESL_Services_Mailplus_Config
	 */
	protected $oConfig = null;

	/**
	 * Instantiates a new MailPlus client.
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * 
	 * @param ESL_Services_Mailplus_Config $oConfig
	 */
	public function __construct(ESL_Services_Mailplus_Config $oConfig)
	{
		$this->oConfig = $oConfig;
	}

	/**
	 * Subscribe an e-mailadres in the E-MAIL channel
	 *
	 * @throws RuntimeException
	 * 
	 * @param ESL_Services_Mailplus_Requests_Subscribe $oRequest
	 * @return string externalId or true if no ID received. False if e-mail is not substribed to email
	 */
	public function subscribe(ESL_Services_Mailplus_Requests_Subscribe $oRequest)
	{
		$oResult = $this->execute('Contacts', 'subscribeContact', $oRequest);

		if (empty($oResult->return->visible)) {
			return false;
		} elseif (empty($oResult->return->channels) || !is_array($oResult->return->channels)) {
			return false;
		} else {
			foreach ($oResult->return->channels as $oChannel) {
				if ($oChannel->name != ESL_Services_Mailplus_Requests_Subscribe::CHANNEL_EMAIL) {
					continue;
				}

				if ($oChannel->active) {
					// E-mail active in email-channel. Return subscription-id
					if (!empty($oResult->return->externalId)) {
						return $oResult->return->externalId;
					} else {
						return true;
					}
				} else {
					// E-mail is inactive for this account
					return false;
				}
			}

			return false;
		}
	}

	/**
	 * Call $sOperation of the webservice with $oRequest as parameter.
	 *
	 * @throws RuntimeException
	 * 
	 * @param string $sService Webservice location
	 * @param string $sOperation Webservice operation
	 * @param ESL_Services_Mailplus_Requests_Interface $oRequest
	 * @return stdClass SOAP response
	 */
	protected function execute($sService, $sOperation, ESL_Services_Mailplus_Requests_Interface $oRequest)
	{
		try {
			$oSoapClient = new SoapClient($this->oConfig->getLocation($sService), $this->oConfig->getConfigOptions());

			$aParameters = array_merge(
				array(
					'id' => $this->oConfig->getApiId(),
					'password' => $this->oConfig->getApiPassword()
				),
				$oRequest->toArray()
			);

			return $oSoapClient->__soapCall($sOperation, array($aParameters));
		} catch (SoapFault $oException) {
			throw new RuntimeException($sOperation . ': ' . $oException->getMessage());
		}
	}
}

?>