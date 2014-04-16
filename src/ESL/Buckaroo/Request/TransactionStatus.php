<?php
/**
 * transactionrequest
 *
 * Defined the fields for a new transaction request
 *
 * @package Buckaroo
 * @version $Id: TransactionStatus.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_Request_TransactionStatus
{
	/**
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 *
	 * @param string $sTransactionKey
	 */
	public function __construct($sTransactionKey)
	{
		$this->aData = array(
			'brq_transaction' => $sTransactionKey
		);
	}

	/**
	 * @return array Request data
	 */
	public function getData()
	{
		return $this->aData;
	}

}
?>