<?php
/**
 * This interface provided a set of methods a userspace handler needs to implement.
 * These methods are what is used by ESL_Buckaroo to feed information back into the implementing code.
 *
 * @package Buckaroo
 * @version $Id: RecurringPaymentCallbackInterface.php 661 2014-02-14 13:44:44Z fpruis $
 */
interface ESL_Buckaroo_RecurringPaymentCallbackInterface
{
	/**
	 * Link the identifier used by the userspace code to the transaction key Buckaroo gave us.
	 *
	 * @param string $sOriginalKey
	 * @param string $sNewKey
	 * @return boolean
	 */
	public function connectTransactions($sOriginalKey, $sNewKey);

	/**
	 * The transaction has succeeded and the payment has been received/approved.
	 *
	 * @param string $sTransactionKey
	 * @return null
	 */
	public function handlePaymentSuccess($sTransactionKey);

	/**
	 * The transaction request contained errors and could not be processed correctly
	 *
	 * @param string $sTransactionKey
	 * @return null
	 */
	public function handlePaymentError($sTransactionKey);

	/**
	 * The transaction was cancelled by the customer or merchant
	 *
	 * @param string $sTransactionKey
	 * @return null
	 */
	public function handlePaymentCancelled($sTransactionKey);

	/**
	 * The transaction has been rejected by the (third party) payment provider.
	 *
	 * @param string $sTransactionKey
	 * @return null
	 */
	public function handlePaymentRejected($sTransactionKey);
}
?>