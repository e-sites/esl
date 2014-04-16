<?php
/**
 * A field available in an action.
 *
 * Methods of payment require certain fields. iDeal requires an issuer. Banktranfer requires personal information, etcetera.
 * This class represents a single field. Some fields can only hold a certain set of values, for example an issuer with iDeal can only be set to one of the supported banks.
 * The set of allowed values can be read with getAllowedValues(). This will either return an array with values or null if there is no limitation on the value.
 *
 * Set the user value with setValue() before passing the field into ESL_Buckaroo:startPayment() to preset this value for the transaction
 *
 * @package Buckaroo
 * @version $Id: CustomField.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_CustomField
{
	/**
	 * Name for this field
	 *
	 * @var string
	 */
	protected $sId;

	/**
	 * Label for this field
	 *
	 * @var string
	 */
	protected $sLabel;

	/**
	 * The datatype
	 *
	 * @var string
	 */
	protected $sDatatype;

	/**
	 * Current value
	 *
	 * @var string
	 */
	protected $sValue;

	/**
	 *
	 * @param array $aParameterMap
	 */
	public function __construct(array $aParameterMap)
	{
		$this->sId = $aParameterMap['name'];
		$this->sLabel = $aParameterMap['description'];
		$this->sDatatype = $aParameterMap['datatype'];
	}

	/**
	 * Unique ID
	 * 
	 * @return string
	 */
	public function getId()
	{
		return $this->sId;
	}

	/**
	 * Label for this field
	 * 
	 * @return string
	 */
	public function getLabel()
	{
		return $this->sLabel;
	}

	/**
	 * The value that was previously set with setValue()
	 * 
	 * @return string
	 */
	public function getUserValue()
	{
		return $this->sValue;
	}

	/**
	 * Sets a value for this field, to be used when creating a new payment.
	 *
	 * If the field has limited allowed values, and a value outside of this allowed list is given, an exception is thrown
	 *
	 * @todo Check value against $this->sDatatype
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $sUserInput
	 */
	public function setValue($sUserInput)
	{
		if (!is_string($sUserInput)) {
			throw new InvalidArgumentException(sprintf("Value for field '%s' must be a string.", $this->getLabel()));
		}

		$this->sValue = $sUserInput;
	}
}
?>