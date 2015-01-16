<?php
/**
 * Factory for TransactionStatus objects.
 *
 * @package Buckaroo
 * @version $Id: TransactionStatus.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_TransactionStatusFactory
{
	/**
	 * @param array $aResponse
	 * @return ESL_Buckaroo_TransactionStatus
	 */
	public static function createTransactionStatus(array $aResponse)
	{
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