<?php
/**
 * transactionrequest
 *
 * Defined the fields for a new transaction request
 *
 * @package Buckaroo
 * @version $Id: TransactionRequest.php 779 2014-09-01 14:16:15Z fpruis $
 */
class ESL_Buckaroo_Request_TransactionRequest
{
	/**
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 * @param ESL_Buckaroo_Payment $oPaymentInfo
	 * @param ESL_Buckaroo_ReturnUrl $oReturnUrl
	 * @param ESL_Buckaroo_Service $oPayService
	 * @param ESL_Buckaroo_Service_Action[] $aAdditionalServiceActions
	 */
	public function __construct(
		ESL_Buckaroo_Payment $oPaymentInfo,
		ESL_Buckaroo_ReturnUrl $oReturnUrl,
		ESL_Buckaroo_Service $oPayService = null,
		array $aAdditionalServiceActions = array())
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

		if ($oPaymentInfo->getIsStartOfRecurrent()) {
			$aData['brq_startrecurrent'] = 'True';
		}

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

		//	Additional services.
		if (count($aAdditionalServiceActions)) {

			// The field 'brq_additional_service' needs a comma-separated list of all the additional services.
			$aData['brq_additional_service'] = implode(
				',',
				array_map(
					function (ESL_Buckaroo_Service_Action $oAction) {
						return $oAction->getService()->getId();
					},
					$aAdditionalServiceActions
				)
			);

			/** @var ESL_Buckaroo_Service_Action $oAction */
			foreach ($aAdditionalServiceActions AS $oAction) {
				$sServiceId = $oAction->getService()->getId();
				$aData['brq_service_' . $sServiceId . '_action'] = $oAction->getName();
				$aData['brq_service_' . $sServiceId . '_version'] = $oAction->getService()->getVersion();

				foreach ($oAction->getFields() as $oField) {
					/* @var $oField ESL_Buckaroo_Service_Field */
					if (null !== ($sUserInput = $oField->getUserValue())) {
						$aData['brq_service_' . $sServiceId . '_' . $oField->getId()] = $sUserInput;
					}
				}
			}
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