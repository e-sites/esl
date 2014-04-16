<?php
/**
 * A service (method of payment) available to Buckaroo
 *
 * Services have an id used for identification, a label suited to display to the end-user (use setLocale in ESL_Buckaroo_Config to get the label in the desired languages) and
 * optionally one or more fields. Fields can be preset when starting a payment to prevent Buckaroo displaying a page to fill them in, to smoothen out the payment process.
 * For example with iDeal payments you can preset the bank (issuer) to be used.
 *
 * You can enable/disable services in your Buckaroo account
 *
 * @package Buckaroo
 * @version $Id: Service.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_Service
{
	/**
	 *
	 * @var string
	 */
	protected $sId;

	/**
	 *
	 * @var string
	 */
	protected $sLabel;

	/**
	 *
	 * @var string
	 */
	protected $sVersion;

	/**
	 *
	 * @var ESL_Buckaroo_Service_Field[]
	 */
	protected $aPayFields;

	/**
	 * Return ESL_Buckaroo_Service or ESL_Buckaroo_Service_Ideal for the given data structure
	 *
	 * @param array $aServiceMap
	 * @return ESL_Buckaroo_Service
	 */
	static public function factory(array $aServiceMap)
	{
		if (empty($aServiceMap['name'])) {
			throw new InvalidArgumentException("Invalid serviceMap provided");
		}

		if ($aServiceMap['name'] == ESL_Buckaroo::SERVICE_IDEAL) {
			$sServiceClass = 'ESL_Buckaroo_Service_Ideal';
		} else {
			$sServiceClass = 'ESL_Buckaroo_Service';
		}
		return new $sServiceClass($aServiceMap);
	}

	/**
	 *
	 * @param array $aServiceMap Array as received from Buckaroo
	 */
	public function __construct(array $aServiceMap)
	{
		$this->sId = $aServiceMap['name'];
		$this->sLabel = $aServiceMap['description'];
		$this->sVersion = $aServiceMap['version'];

		$this->aPayFields = array();
		foreach ($aServiceMap['actiondescription'] as $aActionMap) {
			if ($aActionMap['name'] != 'Pay') {
				continue;
			}

			if (isset($aActionMap['requestparameters'])) {
				foreach ($aActionMap['requestparameters'] as $aParameterMap) {
					$oField = new ESL_Buckaroo_Service_Field($aParameterMap);
					$this->aPayFields[$oField->getId()] = $oField;
				}
			}
			// Stop after we found the Pay-action
			break;
		}
	}

	/**
	 * Unique ID for this service. Matches one of the ESL_Buckaroo::SERVCE_* constants
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->sId;
	}

	/**
	 * UTF-8 encoded label in language defined by ESL_Buckaroo_Config->getLocale()
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return $this->sLabel;
	}

	/**
	 * Service version number
	 *
	 * Internally used
	 *
	 * @access private
	 * @return string
	 */
	public function getVersion()
	{
		return $this->sVersion;
	}

	/**
	 * Return the fields that could be preset for this service.
	 * 
	 * @return ESL_Buckaroo_Service_Field[]
	 */
	public function getFields()
	{
		return $this->aPayFields;
	}

	/**
	 * Return a specific field
	 * 
	 * @throws InvalidArgumentException On invalid Field
	 * 
	 * @return ESL_Buckaroo_Service_Field
	 */
	public function getField($sField)
	{
		$aFields = $this->getFields();
		if (!isset($aFields[$sField])) {
			throw new InvalidArgumentException("Field '$sField' does not exist in service.");
		}
		return $aFields[$sField];
	}
}
?>