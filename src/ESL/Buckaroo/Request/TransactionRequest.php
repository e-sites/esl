<?php
/**
 * transactionrequest
 *
 * Defined the fields for a new transaction request
 *
 * @package Buckaroo
 * @version $Id: TransactionRequest.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_Request_TransactionRequest
{
	/**
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 * 
	 * @param ESL_Buckaroo_Payment $oPaymentInfo
	 * @param ESL_Buckaroo_ReturnUrl $oReturnUrl
	 * @param ESL_Buckaroo_Service $oPayService
	 */
	public function __construct(ESL_Buckaroo_Payment $oPaymentInfo, ESL_Buckaroo_ReturnUrl $oReturnUrl, ESL_Buckaroo_Service $oPayService = null)
	{
		$aData = array();
		$aData['brq_amount'] = number_format($oPaymentInfo->getAmount(), 2, '.', '');
		$aData['brq_currency'] = $oPaymentInfo->getCurrency();
		$aData['brq_invoicenumber'] = $oPaymentInfo->getInvoice();
		$aData['brq_description'] = $oPaymentInfo->getDescription();

		$aData['brq_return'] = $oReturnUrl->getUrlSuccess();
		$aData['brq_returncancel'] = $oReturnUrl->getUrlCancel();
		$aData['brq_returnerror'] = $oReturnUrl->getUrlError();
		$aData['brq_returnreject'] = $oReturnUrl->getUrlReject();

		$aData['brq_continue_on_incomplete'] = 'RedirectToHTML';

		// Prefered method of payment
		if ($oPayService) {
			$sServiceId = $oPayService->getId();
			$aData['brq_payment_method'] = $sServiceId;
			$aData['brq_service_' . $sServiceId . '_action'] = 'Pay';
			$aData['brq_service_' . $sServiceId . '_version'] = $oPayService->getVersion();

			foreach ($oPayService->getFields() as $oField) {
				/* @var $oField ESL_Buckaroo_Service_Field */
				if (null !== ($sUserInput = $oField->getUserValue())) {
					$aData['brq_service_' . $sServiceId . '_' . $oField->getId()] = $sUserInput;
				}
			}
		}

		// Add custom fields to request
		foreach ($oPaymentInfo->getCustomFields() as $oField) {
			/* @var $oField ESL_Buckaroo_CustomField */
			$aData['cust_' . $oField->getId()] = $oField->getUserValue();
		}

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