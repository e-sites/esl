<?php
/**
 * An action that is supported by a Buckaroo service.
 *
 * The action described what exactly the user should 'do' or what should 'happen'.
 * Examples of actions are 'Pay' to start a payment, 'Refund' to refund a previous transaction or 'Subscribe' to subscribe to some type of service (eg 'AutoRecurrent'
 * for recurring payments.)
 *
 *
 * @package Buckaroo
 * @version $Id: Action.php 748 2014-07-29 14:56:52Z jgeerts $
 */
class ESL_Buckaroo_Service_Action
{
	/**
	 * @var ESL_Buckaroo_Service
	 */
	protected $oService;

	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var string
	 */
	protected $sDescription;

	/**
	 * @var @var ESL_Buckaroo_Service_Field[]
	 */
	protected $aFields;

	/**
	 * @param ESL_Buckaroo_Service $oService
	 * @param array $aActionMap
	 */
	function __construct(ESL_Buckaroo_Service $oService, array $aActionMap)
	{
		$this->oService = $oService;
		$this->sName = $aActionMap['name'];
		$this->sDescription = $aActionMap['description'];

		$this->aFields = array();
		if (isset($aActionMap['requestparameters'])) {
			foreach ($aActionMap['requestparameters'] as $aParameterMap) {
				$oField = new ESL_Buckaroo_Service_Field($aParameterMap);
				$this->aFields[$oField->getId()] = $oField;
			}
		}
	}

	/**
	 * Return the fields that could be preset for this service.
	 *
	 * @return ESL_Buckaroo_Service_Field[]
	 */
	public function getFields()
	{
		return $this->aFields;
	}

	/**
	 * Return a specific field
	 *
	 * @param string $sField The ID of the field that should be returned.
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

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->sName;
	}

	/**
	 * @return ESL_Buckaroo_Service
	 */
	public function getService()
	{
		return $this->oService;
	}


} 