<?php
/**
 * canceltransaction
 *
 * Defined the fields for a new transaction request
 *
 * @package Buckaroo
 * @version $Id: CancelTransaction.php 764 2014-08-14 08:37:38Z jgeerts $
 */
class ESL_Buckaroo_Request_CancelTransaction
{
	/**
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 * @param ESL_Buckaroo_Transaction $oTransaction
	 */
	public function __construct(ESL_Buckaroo_Transaction $oTransaction)
	{
		$aData = array();
		$aData['brq_transaction'] = $oTransaction->getTransactionKey();

		$this->aData = $aData;
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