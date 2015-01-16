<?php
/**
 * Represents the status of a transaction.
 *
 * Created with the details sent to us by Buckaroo
 *
 * @package Buckaroo
 * @version $Id: TransactionStatus.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_TransferTransactionStatus extends ESL_Buckaroo_TransactionStatus
{

	protected $sPaymentIban;

	protected $sPaymentReference;

	/**
	 *
	 * @param string $sTransactionKey
	 * @param int $iStatusCode
	 */
	public function __construct($sTransactionKey, $iStatusCode, $sPaymentIban, $sPaymentReference)
	{
		parent::__construct($sTransactionKey, $iStatusCode);
		if (!is_string($sPaymentIban)) {
			throw new InvalidArgumentException("Invalid IBAN");
		}
		if (!is_string($sPaymentReference)) {
			throw new InvalidArgumentException("Invalid reference");
		}

		$this->sPaymentIban = $sPaymentIban;
		$this->sPaymentReference = $sPaymentReference;
	}

	/**
	 *
	 * @deprecated ESL_Buckaroo_TransferTransactionStatus is deprecated and will be replaced with ESL_Buckaroo_TransactionStatus
	 */
	public function getReference()
	{
		trigger_error("ESL_Buckaroo_TransferTransactionStatus is deprecated and will be replaced with ESL_Buckaroo_TransactionStatus", E_USER_DEPRECATED);
		return $this->sPaymentReference;
	}

	/**
	 *
	 * @deprecated ESL_Buckaroo_TransferTransactionStatus is deprecated and will be replaced with ESL_Buckaroo_TransactionStatus
	 */
	public function getIban()
	{
		trigger_error("ESL_Buckaroo_TransferTransactionStatus is deprecated and will be replaced with ESL_Buckaroo_TransactionStatus", E_USER_DEPRECATED);
		return $this->sPaymentIban;
	}
}
?>