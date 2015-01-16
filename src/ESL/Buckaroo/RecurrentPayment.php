<?php
/**
 * Details for a recurrent payment to be executed.
 *
 * Provide the transaction key of the original payment.
 *
 * @package Buckaroo
 * @version $Id: RecurrentPayment.php 759 2014-08-13 14:00:59Z jgeerts $
 */
class ESL_Buckaroo_RecurrentPayment
{
	/**
	 * @var string
	 */
	protected $sOriginalTransactionKey;

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
	 * The identifier should uniquely identify this payment.
	 *
	 * This value will be passed as the first argument to your callbacks saveTransactionKey() when we have obtained a Buckaroo transaction key
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $sOriginalTransactionKey
	 * @param float $fAmount
	 * @param string $sInvoice
	 * @param string $sCurrency Default: EUR
	 */
	public function __construct($sOriginalTransactionKey, $fAmount, $sInvoice, $sCurrency = ESL_Buckaroo_Payment::CURRENCY_EUR)
	{
		if (!is_string($sOriginalTransactionKey)) {
			throw new InvalidArgumentException("Argument #1 (OriginalTransactionKey) is required to be a string");
		}
		if (!is_float($fAmount)) {
			throw new InvalidArgumentException("Argument #2 (amount) is required to be a float");
		}
		if ($fAmount <= 0) {
			throw new InvalidArgumentException("Argument #2 (amount) can not be zero or less");
		}
		if (!is_string($sInvoice)) {
			throw new InvalidArgumentException("Argument #3 (invoice) is required to be a string");
		}
		if (!in_array($sCurrency, array(ESL_Buckaroo_Payment::CURRENCY_EUR, ESL_Buckaroo_Payment::CURRENCY_USD))) {
			throw new InvalidArgumentException("Argument #4 (currency) is invalid. Use either ESL_Buckaroo_Payment::CURRENCY_EUR or ESL_Buckaroo_Payment::CURRENCY_USD");
		}

		$this->sOriginalTransactionKey = $sOriginalTransactionKey;
		$this->fAmount = round($fAmount, 2);
		$this->sCurrency = $sCurrency;
		$this->sInvoice = $sInvoice;
	}

	/**
	 * @return string
	 */
	public function getOriginalTransactionKey()
	{
		return $this->sOriginalTransactionKey;
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
}
?>