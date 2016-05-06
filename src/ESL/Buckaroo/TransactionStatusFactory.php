<?php
/**
 * Factory for TransactionStatus objects.
 *
 * @package Buckaroo
 * @version $Id: TransactionStatus.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_TransactionStatusFactory
{
	/*
	 * Buckaroo status codes we need in here.
	 */
	const STATUSCODE_SUCCES = 190;
	const STATUSCODE_FAIL = 490;

	/**
	 * @param array $aResponse
	 * @param ESL_Buckaroo_Gateway $oGateway
	 * @return ESL_Buckaroo_TransactionStatus
	 */
	public static function createTransactionStatus(array $aResponse, ESL_Buckaroo_Gateway $oGateway = null)
	{
		/*
		 * Calling this method without a gateway is deprecated.
		 * For all calls, we'll trigger a E_USER_DEPRECATED so that people notice.
		 */
		if (!$oGateway) {
			trigger_error("Calling " . __METHOD__ . " without passing a gateway is deprecated.", E_USER_DEPRECATED);
		}


		/*
		 * If there is a transaction group, fetch the status for the group and use that instead of the original status.
		 * This happens if a user started paying with a "limited funds" payment method, eg. giftcard, and then had to do
		 * another payment to complete the payment. (eg €20 order, €5 giftcard and €15 iDEAL payment).
		 * Note that this second payment can, again, be a limited funds payment. (€20 order can be payed by using 4 €5 giftcards.)
		 */
		if (!empty($aResponse['brq_relatedtransaction_partialpayment'])) {

			/*
			 * $oGateway is really needed here.
			 * If we do not have it, trigger an error and throw an exception.
			 */
			if (!$oGateway) {
				$sExceptionMessage = sprintf(
					"%s cannot handle transaction %s because it has a 'brq_relatedtransaction_partialpayment', but no gateway to retreive the required information.",
					__METHOD__, $aResponse['brq_statuscode']
				);
				trigger_error($sExceptionMessage, E_USER_WARNING);
				throw new RuntimeException($sExceptionMessage);
			}

			$oGroupRequest = new ESL_Buckaroo_Request_TransactionStatus($aResponse['brq_relatedtransaction_partialpayment']);
			$aGroupResponse = $oGateway->transactionStatus($oGroupRequest);

			//	Use the group status code, because we do not care about whether or not the partial payment succeeded,
			//	we want to know if the entire payment succeeded.
			$aResponse['brq_statuscode'] = $aGroupResponse['brq_statuscode'];
		}

		/*
		 * Some transactions can be reversed.
	 	 * If that happens, the way we want to deal with it is act as if the original transaction was cancled.
		 */
		if (!empty($aResponse['brq_relatedtransaction_reversal']) && $aResponse['brq_statuscode'] == static::STATUSCODE_SUCCES) {

			/*
			 * $oGateway is really needed here.
			 * If we do not have it, trigger an error and throw an exception.
			 */
			if (!$oGateway) {
				$sExceptionMessage = sprintf(
					"%s cannot handle transaction %s because it has a 'brq_relatedtransaction_reversal', but no gateway to retreive the required information.",
					__METHOD__, $aResponse['brq_statuscode']
				);
				trigger_error($sExceptionMessage, E_USER_WARNING);
				throw new RuntimeException($sExceptionMessage);
			}

			$aResponse['brq_transactions'] = $aResponse['brq_relatedtransaction_reversal'];
			$aResponse['brq_statuscode'] = static::STATUSCODE_FAIL;

			//	Retrieve the original transaction.
			$oReversedTransactionRequest = new ESL_Buckaroo_Request_TransactionStatus($aResponse['brq_relatedtransaction_reversal']);
			$aReversedTransactionResponse = $oGateway->transactionStatus($oReversedTransactionRequest);

			//	If the original transaction was recurring, we should handle this is canceling a recurring transaction.
			if (!empty($aReversedTransactionResponse['brq_recurring'])) {
				$aResponse['brq_recurring'] = $aReversedTransactionResponse['brq_recurring'];
			}
		}

		if (empty($aResponse['brq_transaction_method']) || empty($aResponse['brq_service_transfer_iban'])) {
			$oStatus = new ESL_Buckaroo_TransactionStatus($aResponse['brq_transactions'], $aResponse['brq_statuscode']);
		} else {
			switch ($aResponse['brq_transaction_method']) {
				case 'transfer':
					$oStatus = new ESL_Buckaroo_TransferTransactionStatus(
						$aResponse['brq_transactions'],
						$aResponse['brq_statuscode'],
						$aResponse['brq_service_transfer_iban'],
						$aResponse['brq_service_transfer_paymentreference']
					);
					break;

				default:
					$oStatus = new ESL_Buckaroo_TransactionStatus($aResponse['brq_transactions'], $aResponse['brq_statuscode']);
			}
		}

		if (isset($aResponse['brq_recurring']) && $aResponse['brq_recurring'] == 'True') {
			$oStatus->markAsRecurrent();
		}

		return $oStatus;
	}
}
?>