<?php
/**
 * Represents the status of a transaction.
 *
 * Created with the details sent to us by Buckaroo
 *
 * @package Buckaroo
 * @version $Id: TransactionStatus.php 684 2014-04-16 08:39:18Z fpruis $
 */
class ESL_Buckaroo_TransactionStatus
{
	/**
	 * Transaction key
	 *
	 * Possibly is a comma seperated list of multiple keys
	 * 
	 * @var string
	 */
	protected $sTransactionKey;

	/**
	 * Status code
	 *
	 * Final responses
	 * 190 Success: The transaction has succeeded and the payment has been received/approved.
	 * 490 Failed: The transaction has failed.
	 * 491 Validation Failure:  The transaction request contained errors and could not be processed correctly
	 * 492 Technical Failure: Some technical failure prevented the completion of the transactions
	 * 890 Cancelled By User: The transaction was cancelled by the customer.
	 * 891 Cancelled By Merchant: The merchant cancelled the transaction.
	 * 690 Rejected: The transaction has been rejected by the (third party) payment provider.
	 *
	 * Non-final responses
	 * 790 Pending Input: The transaction is on hold while the payment engine is waiting on further  input from the consumer.
	 * 791 Pending Processing: The transaction is being processed
	 * 792 Awaiting Consumer:  The Payment Engine is waiting for the consumer to return from a third party website, needed to complete the transaction
	 * 793 On Hold: The Payment Engine has put the payment on hold because the merchant has insufficient balance to perform the payment (eg. Refund)
	 * 
	 * @var int
	 */
	protected $iStatusCode;

	/**
	 *
	 * @param string $sTransactionKey
	 * @param int $iStatusCode
	 */
	public function __construct($sTransactionKey, $iStatusCode)
	{
		if (!is_string($sTransactionKey)) {
			throw new InvalidArgumentException("Invalid transaction key");
		}
		if (!is_numeric($iStatusCode)) {
			throw new InvalidArgumentException("Invalid statuscode");
		}

		$this->sTransactionKey = $sTransactionKey;
		$this->iStatusCode = (int) $iStatusCode;
	}

	/**
	 * Transaction key
	 *
	 * Could be a comma seperated list of multiple keys
	 * 
	 * @return string
	 */
	public function getTransactionKey()
	{
		return $this->sTransactionKey;
	}

	/**
	 * Return numeric status code as provided by Buckaroo
	 *
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->iStatusCode;
	}

	/**
	 * Returns either of the ESL_Buckaroo::STATUS_* constants, or null if the statuscode could not be resolved into a textual status
	 *
	 *
	 * @return string
	 */
	public function getStatus()
	{
		switch ($this->iStatusCode) {
			case 190:
				return ESL_Buckaroo::STATUS_SUCCESS;
				break;

			case 490:
			case 491:
			case 492:
				return ESL_Buckaroo::STATUS_ERROR;
				break;

			case 890:
			case 891:
				return ESL_Buckaroo::STATUS_CANCEL;
				break;

			case 690:
				return ESL_Buckaroo::STATUS_REJECT;
				break;

			case 792:
				return ESL_Buckaroo::STATUS_WAITING;
		}

		// Status unknown
		return null;
	}

	/**
	 * Returns whether transaction was succesfull
	 * 
	 * @return bool
	 */
	public function isSuccess()
	{
		return ($this->getStatus() == ESL_Buckaroo::STATUS_SUCCESS);
	}

	/**
	 * Returns whether transaction triggered an error and was unsuccessfull
	 *
	 * @return bool
	 */
	public function isError()
	{
		return ($this->getStatus() == ESL_Buckaroo::STATUS_ERROR);
	}

	/**
	 * Returns whether transaction was cancelled
	 *
	 * @return bool
	 */
	public function isCancelled()
	{
		return ($this->getStatus() == ESL_Buckaroo::STATUS_CANCEL);
	}

	/**
	 * Returns whether transaction was rejected
	 *
	 * @return bool
	 */
	public function isRejected()
	{
		return ($this->getStatus() == ESL_Buckaroo::STATUS_REJECT);
	}

	/**
	 * Returns whether transaction is waiting for user input
	 *
	 * @return bool
	 */
	public function isWaiting()
	{
		return ($this->getStatus() == ESL_Buckaroo::STATUS_WAITING);
	}
}
?>