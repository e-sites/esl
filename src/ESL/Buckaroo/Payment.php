<?php
/**
 * Details for a payment to be created.
 *
 * Provide the identifier, amount and currency for a payment, and use the instance with ESL_Buckaroo->newPayment()
 *
 * Note the amount can not be zero (or less)
 *
 * @package Buckaroo
 * @version $Id: Payment.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_Payment
{
	const CURRENCY_EUR = 'EUR';
	const CURRENCY_USD = 'USD';

	/**
	 * The identifier that makes sense in userland
	 * For example, in a webshop, this would be something like the orderID.
	 *
	 * @var string $sIdentifier
	 */
	protected $sIdentifier;

	/**
	 * The amount of money to be transfered.
	 *
	 * @var float $fAmount
	 */
	protected $fAmount;

	/**
	 * The currency to be used. eg EUR, USD, GBP.
	 * Defaults to 'EUR'.
	 *
	 * @var string $sCurrency
	 */
	protected $sCurrency;

	/**
	 * The invoice number that should show up on the bill.
	 * This should be something the customer, administrator, etc. can all use
	 * to communicate about the payment/order without any ambiguity.
	 *
	 * @var string $sInvoice
	 */
	protected $sInvoice = '';

	/**
	 * The description to show up on the invoice.
	 *
	 * @var string $sDescription
	 */
	protected $sDescription = '';

	/**
	 *
	 * @var string
	 */
	protected $oPayService;

	/**
	 * A list of custom fields and their values.
	 *
	 * @var ESL_Buckaroo_CustomField[]
	 */
	protected $aCustomFields = array();

	/**
	 * The identifier should uniquely identify this payment.
	 *
	 * This value will be passed as the first argument to your callbacks saveTransactionKey() when we have obtained a Buckaroo transaction key
	 * 
	 * @throws InvalidArgumentException
	 *
	 * @param string $sIdentifier Your orderId
	 * @param float $fAmount
	 * @param string $sCurrency Default: EUR
	 */
	public function __construct($sIdentifier, $fAmount, $sCurrency = self::CURRENCY_EUR)
	{
		if (!is_string($sIdentifier)) {
			throw new InvalidArgumentException("Argument #1 (identifier) is required to be a string");
		}
		if (!is_float($fAmount)) {
			throw new InvalidArgumentException("Argument #2 (amount) is required to be a float");
		}
		if ($fAmount <= 0) {
			throw new InvalidArgumentException("Argument #2 (amount) can not be zero or less");
		}
		if (!is_string($sCurrency)) {
			throw new InvalidArgumentException("Argument #3 (currency) is required to be a string");
		}
		if (!in_array($sCurrency, array(static::CURRENCY_EUR, static::CURRENCY_USD))) {
			throw new InvalidArgumentException("Argument #3 (currency) is invalid. Use either ESL_Buckaroo_Payment::CURRENCY_EUR or ESL_Buckaroo_Payment::CURRENCY_USD");
		}

		$this->sIdentifier = $sIdentifier;
		$this->fAmount = round($fAmount, 2);
		$this->sCurrency = $sCurrency;

		// Invoice number is by default the identifier, but can be overwritten with anything else
		$this->sInvoice = $sIdentifier;
	}

	/**
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->sIdentifier;
	}

	/**
	 *
	 * @return float
	 */
	public function getAmount()
	{
		return $this->fAmount;
	}

	/**
	 *
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->sCurrency;
	}

	/**
	 *
	 * @param string $sDescription
	 */
	public function setDescription($sDescription)
	{
		if (!is_string($sDescription)) {
			throw new InvalidArgumentException("Argument is required to be a string");
		}
		$this->sDescription = $sDescription;
	}

	/**
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->sDescription;
	}

	/**
	 *
	 * @param string $sInvoice
	 */
	public function setInvoice($sInvoice)
	{
		if (!is_string($sInvoice)) {
			throw new InvalidArgumentException("Argument is required to be a string");
		}
		$this->sInvoice = $sInvoice;
	}

	/**
	 *
	 * @return string
	 */
	public function getInvoice()
	{
		return $this->sInvoice;
	}

	/**
	 * Add a custom field that should be included in the payment request.
	 *
	 * @throws InvalidArgumentException If the field has no value.
	 * 
	 * @param ESL_Buckaroo_CustomField $oField
	 */
	public function addCustomField(ESL_Buckaroo_CustomField $oField)
	{
		if (!$oField->getUserValue()) {
			throw new InvalidArgumentException(sprintf("Custom fields '%s' was added without setting its value.", $oField->getId()));
		}

		$this->aCustomFields[$oField->getId()] = $oField;
	}

	/**
	 * @return ESL_Buckaroo_CustomField[]
	 */
	public function getCustomFields()
	{
		return $this->aCustomFields;
	}
}
?>