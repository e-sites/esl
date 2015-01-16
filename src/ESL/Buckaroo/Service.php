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
 * @version $Id: Service.php 767 2014-08-20 06:20:50Z fpruis $
 */
class ESL_Buckaroo_Service
{
	const ACTION_PAY = 'Pay';
	const ACTION_PAYRECURRENT = 'PayRecurrent';
	const ACTION_SUBSCRIBE = 'Subscribe';

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
	 * List of actions supported by the service.
	 *
	 * @var ESL_Buckaroo_Service_Action[]
	 */
	protected $aSupportedActions = array();

	/**
	 * Return ESL_Buckaroo_Service or ESL_Buckaroo_Service_Ideal for the given data structure
	 *
	 * @param array $aServiceMap
	 * @return ESL_Buckaroo_Service
	 * @throws InvalidArgumentException
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

		$this->aSupportedActions = array();
		foreach ($aServiceMap['actiondescription'] as $aActionMap) {
			$this->aSupportedActions[$aActionMap['name']] = new ESL_Buckaroo_Service_Action($this, $aActionMap);
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
	 * Return the fields that could be preset for the pay action of this service.
	 * Shorthand for getAction('Pay')->getFields()
	 *
	 * @return ESL_Buckaroo_Service_Field[]
	 */
	public function getFields()
	{
		return $this->getAction(static::ACTION_PAY)->getFields();
	}

	/**
	 * Return a specific field of the pay action of this service.
	 * Shorthand for getAction('Pay')->getField($sField)
	 *
	 * @param string $sField The ID of the field that should be returned.
	 *
	 * @throws InvalidArgumentException On invalid Field
	 *
	 * @return ESL_Buckaroo_Service_Field
	 */
	public function getField($sField)
	{
		return $this->getAction(static::ACTION_PAY)->getField($sField);
	}

	/**
	 * Does this service support a specific action?
	 *
	 * @param string $sActionName The name of the action for which you want to know if it is supported, eg ESL_Buckaroo_Status::ACTION_PAY
	 *
	 * @return bool
	 */
	public function supportsAction($sActionName)
	{
		return isset($this->aSupportedActions[$sActionName]);
	}

	/**
	 * Get a specific action.
	 *
	 * @param $sActionName
	 * @return ESL_Buckaroo_Service_Action
	 * @throws InvalidArgumentException
	 */
	public function getAction($sActionName)
	{
		if (!$this->supportsAction($sActionName)) {
			throw new InvalidArgumentException("Action '$sActionName' is not supported by service.");
		}

		return $this->aSupportedActions[$sActionName];
	}
}
?>