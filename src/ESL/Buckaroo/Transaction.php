<?php
/**
 * Represents a transaction.
 *
 * @package Buckaroo
 * @version $Id: Transaction.php 765 2014-08-15 11:38:16Z jgeerts $
 */
class ESL_Buckaroo_Transaction
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
	 * @param string $sTransactionKey
	 */
	function __construct($sTransactionKey)
	{
		$this->sTransactionKey = $sTransactionKey;
	}

	/**
	 * @return string
	 */
	public function getTransactionKey()
	{
		return $this->sTransactionKey;
	}
}
?>