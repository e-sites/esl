<?php
/**
 * @author jgeerts@e-sites.nl
 * @since 8/14/14 2:32 PM
 */

class ESL_Buckaroo_Request_PayRecurrent
{
	/**
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 * @param ESL_Buckaroo_RecurrentPayment $oPayment
	 * @param ESL_Buckaroo_Service $oPayService
	 */
	public function __construct(ESL_Buckaroo_RecurrentPayment $oPayment, ESL_Buckaroo_Service $oPayService = null)
	{
		$aData = array();

		$sServiceId = $oPayService->getId();
		$aData['brq_payment_method'] = $sServiceId;
		$aData['brq_service_' . $sServiceId . '_action'] = 'PayRecurrent';
		$aData['brq_service_' . $sServiceId . '_version'] = $oPayService->getVersion();
		$aData['brq_originaltransaction'] = $oPayment->getOriginalTransactionKey();
		$aData['brq_amount'] = number_format($oPayment->getAmount(), 2, '.', '');
		$aData['brq_currency'] = $oPayment->getCurrency();
		$aData['brq_invoicenumber'] = $oPayment->getInvoice();
		$aData['brq_description'] = $oPayment->getDescription();

		foreach ($oPayService->getAction(ESL_Buckaroo_Service::ACTION_PAYRECURRENT)->getFields() as $oField) {
			/* @var $oField ESL_Buckaroo_Service_Field */
			if (null !== ($sUserInput = $oField->getUserValue())) {
				$aData['brq_service_' . $sServiceId . '_' . $oField->getId()] = $sUserInput;
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