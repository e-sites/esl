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
 * @version $Id: Field.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_Service_Field
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
	 * Whether this field is required to process a payment
	 *
	 * If a field is required but the value left empty, Buckaroo will state the customer for the value. It is not actually required to pass the value with startPayment(), that's
	 * only the case if you want to avoid Buckaroo's intermediate pages.
	 * 
	 * @var bool
	 */
	protected $bRequired;

	/**
	 * Set of values allowed to be used
	 * 
	 * @var array
	 */
	protected $aAllowedValues;

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
		$this->sLabel = $aParameterMap['displayname'];
		$this->bRequired = (strtolower($aParameterMap['required']) == 'true');

		if (isset($aParameterMap['list'])) {
			foreach ($aParameterMap['listitemdescription'] as $aOption) {
				$this->aAllowedValues[$aOption['value']] = $aOption['description'];
			}
		} else {
			$this->aAllowedValues = null;
		}
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
	 * Whether a value is required to be abled to complete payment
	 *
	 * It is not required to provide this value with newPayment(). If a value is omitted Buckaroo will state the user for this value
	 * 
	 * @return bool
	 */
	public function isRequired()
	{
		return $this->bRequired;
	}

	/**
	 * Returns list of values thay may be used with this field
	 * 
	 * @return array Or NULL if no specific values are required
	 */
	public function getAllowedValues()
	{
		return $this->aAllowedValues;
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
	 * @throws InvalidArgumentException
	 *
	 * @param string $sUserInput
	 */
	public function setValue($sUserInput)
	{
		if (!is_string($sUserInput)) {
			throw new InvalidArgumentException(sprintf("Value for field '%s' must be a string.", $this->getLabel()));
		}
		if ($this->aAllowedValues && !array_key_exists($sUserInput, $this->aAllowedValues)) {
			throw new InvalidArgumentException(sprintf("Value '%s' is invalid for field '%s'", $sUserInput, $this->getLabel()));
		}

		$this->sValue = $sUserInput;
	}
}
?>