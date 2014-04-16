<?php
/**
 * Buckaroo client to process online payments
 *
 * Provides methods to create a new transaction and to check the status for an existing transaction. During instantiation a custom object implementing
 * ESL_Buckaroo_CallbackInterface has to be provided which will be called to process payments.
 *
 * @package Buckaroo
 * @version $Id: Buckaroo.php 684 2014-04-16 08:39:18Z fpruis $
 */
class ESL_Buckaroo
{
	/**
	 * Matches a ESL_Buckaroo_Service->getId()
	 */
	const SERVICE_IDEAL         = 'ideal';
	const SERVICE_VISA          = 'visa';
	const SERVICE_MASTERCARD    = 'mastercard';
	const SERVICE_MAESTRO       = 'maestro';
	const SERVICE_GIROPAY       = 'giropay';
	const SERVICE_TRANSFER      = 'transfer';
	const SERVICE_SOFORTBANKING = 'sofortueberweisung';
	const SERVICE_VPAY          = 'VPay';
	const SERVICE_MRCASH        = 'bancontactmrcash';

	/**
	 * Used in QUERYSTRING_PUSHMESSAGE when customer is send back to us after payment is completed (either succeeded or not)
	 */
	const STATUS_SUCCESS = 'success';
	const STATUS_CANCEL  = 'cancel';
	const STATUS_ERROR   = 'error';
	const STATUS_REJECT  = 'reject';
	const STATUS_WAITING = 'waiting';

	/**
	 * Name of querystring variable that is set with a status when customer is redirected back to us. Only used when no custom url is provided in ESL_Buckaroo_ReturnUrl
	 *
	 * When the visitor is send back a pushmessage is also available
	 */
	const QUERYSTRING_PUSHMESSAGE = 'status';

	/**
	 * Configuration of merchant key, locale, etcetera.
	 * 
	 * @var ESL_Buckaroo_Config
	 */
	protected $oConfig;

	/**
	 * Userland object to process transaction statusses
	 * 
	 * @var ESL_Buckaroo_CallbackInterface
	 */
	protected $oCallbacks;

	/**
	 * Buckaroo gateway used to communicate
	 * 
	 * @var ESL_Buckaroo_Gateway
	 */
	protected $oGateway;

	/**
	 * Available payment methods
	 * 
	 * @var ESL_Buckaroo_Service[]
	 */
	protected $aServices;

	/**
	 * @var ESL_Buckaroo_CustomField[]
	 */
	protected $aCustomFields;

	/**
	 * Provide an ESL_Buckaroo_Config instance with your settings, and an ESL_Buckaroo_CallbackInterface implementing class that will be called to process updates
	 * 
	 * @param ESL_Buckaroo_Config $oConfig Configuration
	 * @param ESL_Buckaroo_CallbackInterface $oCallbacks
	 */
	public function __construct(ESL_Buckaroo_Config $oConfig, ESL_Buckaroo_CallbackInterface $oCallbacks)
	{
		$this->oConfig = $oConfig;
		$this->oCallbacks = $oCallbacks;
	}

	/**
	 * Load the request specification from Buckaroo and use it to define our payment services and custom fields.
	 *
	 * @throws RuntimeException
	 */
	protected function loadRequestSpecification()
	{
		if (null !== $this->aServices) {
			// Services are already known and don't need to be reloaded
			return;
		}

		// Retreive services and custom fields
		$aResponse = $this->getGateway()->transactionRequestSpecification();

		// Inflate flat response into multidimensional array for easier processing
		$aResponseParsed = array();
		foreach ($aResponse as $sKey => $sValue) {
			// Unset from source to keep memory footprint to a minimum as we go
			unset($aResponse[$sKey]);

			if (strpos($sKey, 'brq_services_') !== 0 && strpos($sKey, 'brq_customparameters_') !== 0) {
				// No interest in anything else
				continue;
			}

			$aKeyParts = explode('_', $sKey);
			// Final key where the value shall be stored
			$sValueKey = array_pop($aKeyParts);

			$aServiceValue = &$aResponseParsed;
			// Recurse into array, define branches that do not yet exists, into where the value needs to be set
			foreach ($aKeyParts as $sKeyPart) {
				if (!isset($aServiceValue[$sKeyPart])) {
					$aServiceValue[$sKeyPart] = array();
				}
				$aServiceValue =& $aServiceValue[$sKeyPart];
			}
			// Set value in final node
			$aServiceValue[$sValueKey] = $sValue;
		}

		// Create ESL_Buckaroo_Service classes from response data
		if (empty($aResponseParsed['brq']['services'])) {
			throw new RuntimeException("No methods of payment available");
		}

		$this->aServices = array();
		foreach ($aResponseParsed['brq']['services'] as $aServiceMap) {
			$oService = ESL_Buckaroo_Service::factory($aServiceMap);
			$this->aServices[$oService->getId()] = $oService;
		}

		$this->aCustomFields = array();
		if (!empty($aResponseParsed['brq']['customparameters'])) {
			foreach ($aResponseParsed['brq']['customparameters'] as $aCustomParam) {
				$oCustomField = new ESL_Buckaroo_CustomField($aCustomParam);
				$this->aCustomFields[$oCustomField->getId()] = $oCustomField;
			}
		}
	}

	/**
	 * Get the available methods of payment (services)
	 *
	 * You are not obligated to query for the available services. It is also perfectly fine to create a new payment without service-information. Then Buckaroo will prompt the
	 * customer for this information themselves.
	 *
	 * @throws RuntimeException If there are no services available.
	 *
	 * @return ESL_Buckaroo_Service[] Available services
	 */
	public function getPayServices()
	{
		$this->loadRequestSpecification();
		return $this->aServices;
	}

	/**
	 * Get the available custom fields.
	 *
	 * These fields are configured in the Buckaroo Payment Plaza and are never required.
	 *
	 * @throws RuntimeException If Buckaroo it's request specification is invalid.
	 *
	 * @return ESL_Buckaroo_CustomField[]
	 */
	public function getCustomFields()
	{
		$this->loadRequestSpecification();
		return $this->aCustomFields;
	}

	/**
	 * Return one of the available services, to be used with ESL_Buckaroo_Payment.
	 *
	 * Use getPayServices() and/or a ESL_Buckaroo::SERVICE_* constant
	 * 
	 * @throws InvalidArgumentException On non-available service
	 * 
	 * @param string $sService
	 * @return ESL_Buckaroo_Service
	 */
	public function getPayService($sService)
	{
		$aServices = $this->getPayServices();
		if (!isset($aServices[$sService])) {
			throw new InvalidArgumentException("Service '$sService' is not available");
		}
		return $aServices[$sService];
	}

	/**
	 * Create a new transaction with Buckaroo and return the URL where the customer should be send to complete payment
	 *
	 * This method will return a URL where the end-user should be redirected and where the payment can be completed
	 * 
	 * @throws RuntimeException
	 *
	 * @param ESL_Buckaroo_Payment $oPaymentInfo
	 * @param ESL_Buckaroo_ReturnUrl $oReturnUrl
	 * @param ESL_Buckaroo_Service $oPayService Optional. Buckaroo Service that customer prefers to use
	 * @return string Buckaroo URL where customer should be send
	 */
	public function createPayment(ESL_Buckaroo_Payment $oPaymentInfo, ESL_Buckaroo_ReturnUrl $oReturnUrl, ESL_Buckaroo_Service $oPayService = null)
	{
		$oRequest = new ESL_Buckaroo_Request_TransactionRequest($oPaymentInfo, $oReturnUrl, $oPayService);

		$aResponse = $this->getGateway()->transactionRequest($oRequest);

		//	We either have a result, or an error.
		if (empty($aResponse['brq_apiresult'])) {
			throw new RuntimeException($aResponse['brq_statusmessage'], $aResponse['brq_statuscode']);
		}

		if (empty($aResponse['brq_transactions'])) {
			throw new RuntimeException("No transaction key received.");
		}

		$sTransactionKey = $aResponse['brq_transactions'];

		// Link the userland orderID to the Buckaroo transaction
		if (!$this->oCallbacks->saveTransactionKey($oPaymentInfo->getIdentifier(), $sTransactionKey)) {
			throw new RuntimeException("Could not map Buckaroo transaction '$sTransactionKey' to payment identifier '" . $oPaymentInfo->getIdentifier() . "'.");
		}

		if (isset($aResponse['brq_actionrequired']) && $aResponse['brq_actionrequired'] == 'redirect') {
			$sReturnUrl = $aResponse['brq_redirecturl'];
		} else {
			// It's either finished (success), or an error. In either case redirect customer to our own site, to the url we have provided ourselves
			switch (strtolower($aResponse['brq_apiresult'])) {
				case 'success':
				case 'waiting':
					$sReturnUrl = empty($aResponse['brq_return'])?$oReturnUrl->getUrlSuccess():$aResponse['brq_return']; // Same as $oReturnUrl->getUrlSuccess()
					break;
				case 'cancel':
					$sReturnUrl = $aResponse['brq_returncancel']; // Same as $oReturnUrl->getUrlCancel()
					break;
				case 'reject':
					$sReturnUrl = $aResponse['brq_returnreject']; // Same as $oReturnUrl->getUrlReject()
					break;
				default:
					$sReturnUrl = $aResponse['brq_returnerror']; // Same as $oReturnUrl->getUrlError()
					break;
			}
		}

		return $sReturnUrl;
	}

	/**
	 * Create a new transaction with Buckaroo and redirect the customer to the URL to complete payment
	 *
	 * Method exit()'s the current process after sending the redirect headers
	 *
	 * @throws RuntimeException
	 *
	 * @param ESL_Buckaroo_Payment $oPaymentInfo
	 * @param ESL_Buckaroo_ReturnUrl $oReturnUrl
	 * @param ESL_Buckaroo_Service $oPayService Optional. Buckaroo Service that customer prefers to use
	 * @return null
	 */
	public function doPayment(ESL_Buckaroo_Payment $oPaymentInfo, ESL_Buckaroo_ReturnUrl $oReturnUrl, ESL_Buckaroo_Service $oPayService = null)
	{
		$sReturnUrl = $this->createPayment($oPaymentInfo, $oReturnUrl, $oPayService);
		header('HTTP/1.1 302 Found');
		header('Location: ' . $sReturnUrl);
		exit();
	}

	/**
	 * Receive and handle a status push.
	 *
	 * When a customer is redirected from Buckaroo back to us after finishing the transaction, Buckaroo submits a form with transaction information
	 * This form is called a push message, and this method processes the contents
	 *
	 * In most cases you call this method with the entire $_POST superglobal as argument on the page you redirect customers to.
	 * 
	 * @throws RuntimeException
	 *
	 * @param array $aPushmessage
	 * @return ESL_Buckaroo_TransactionStatus
	 */
	public function processPushmessage(array $aPushmessage)
	{
		// Urldecode values
		foreach ($aPushmessage as $sKey => $sValue) {
			$aPushmessage[$sKey] = urldecode($sValue);
		}

		if (isset($aPushmessage['brq_transactions'])) {
			$sTransaction = $aPushmessage['brq_transactions'];
		} elseif (isset($aPushmessage['BRQ_TRANSACTIONS'])) {
			$sTransaction = $aPushmessage['BRQ_TRANSACTIONS'];
		} else {
			// There is no pushmessage
			return false;
		}

		if (isset($aPushmessage['brq_signature'])) {
			$sSignature = $aPushmessage['brq_signature'];
		} elseif (isset($aPushmessage['BRQ_SIGNATURE'])) {
			$sSignature = $aPushmessage['BRQ_SIGNATURE'];
		} else {
			// Incomplete pushmessage
			throw new RuntimeException("No signature in pushmessage.");
		}

		if (isset($aPushmessage['brq_statuscode'])) {
			$sStatuscode = $aPushmessage['brq_statuscode'];
		} elseif (isset($aPushmessage['BRQ_STATUSCODE'])) {
			$sStatuscode = $aPushmessage['BRQ_STATUSCODE'];
		} else {
			// Incomplete pushmessage
			throw new RuntimeException("No status code in pushmessage.");
		}

		// Verify signature
		if (!$this->getGateway()->verifySignature($aPushmessage, $sSignature)) {
			throw new RuntimeException("Invalid signature in pushmessage.");
		}

		/*
		 * If there is a transaction group, fetch the status for the group and use that instead of the original status.
		 * This happens if a user started paying with a "limited funds" payment method, eg. giftcard, and then had to do
		 * another payment to complete the payment. (eg €20 order, €5 giftcard and €15 iDEAL payment).
		 * Note that this second payment can, again, be a limited funds payment. (€20 order can be payed by using 4 €5 giftcards)
		 */
		if (!empty($aPushmessage['brq_relatedtransaction_partialpayment'])) {
			$sGroupTransaction = $aPushmessage['brq_relatedtransaction_partialpayment'];
		} elseif (!empty($aPushmessage['BRQ_RELATEDTRANSACTION_PARTIALPAYMENT'])) {
			$sGroupTransaction = $aPushmessage['BRQ_RELATEDTRANSACTION_PARTIALPAYMENT'];
		} else {
			$sGroupTransaction = null;
		}

		if ($sGroupTransaction) {
			$oGroupRequest = new ESL_Buckaroo_Request_TransactionStatus($sGroupTransaction);
			$aGroupResponse = $this->getGateway()->transactionStatus($oGroupRequest);

			//	Use the group status code, because we do not care about whether or not the partial payment succeeded,
			//	we want to know if the entire payment succeeded.
			$sStatuscode = $aGroupResponse['brq_statuscode'];
		}

		$oStatus = new ESL_Buckaroo_TransactionStatus($sTransaction, $sStatuscode);
		$this->handleTransactionStatus($oStatus);

		return $oStatus;
	}

	/**
	 * Process status for given transaction
	 *
	 * The status is retreived and the approritate method in the user callback is called.
	 *
	 * @param string $sTransactionKey
	 * @return ESL_Buckaroo_TransactionStatus
	 */
	public function checkTransactionStatus($sTransactionKey)
	{
		$oRequest = new ESL_Buckaroo_Request_TransactionStatus($sTransactionKey);
		$aResponse = $this->getGateway()->transactionStatus($oRequest);
		
		if (empty($aResponse['brq_transactions'])) {
			throw new RuntimeException("No transaction key in response.");
		}
		if (empty($aResponse['brq_statuscode'])) {
			throw new RuntimeException("No status code in response.");
		}

		/*
		 * If there is a transaction group, fetch the status for the group and use that instead of the original status.
		 * This happens if a user started paying with a "limited funds" payment method, eg. giftcard, and then had to do
		 * another payment to complete the payment. (eg €20 order, €5 giftcard and €15 iDEAL payment).
		 * Note that this second payment can, again, be a limited funds payment. (€20 order can be payed by using 4 €5 giftcards.)
		 */
		if (!empty($aResponse['brq_relatedtransaction_partialpayment'])) {
			$oGroupRequest = new ESL_Buckaroo_Request_TransactionStatus($aResponse['brq_relatedtransaction_partialpayment']);
			$aGroupResponse = $this->getGateway()->transactionStatus($oGroupRequest);

			//	Use the group status code, because we do not care about whether or not the partial payment succeeded,
			//	we want to know if the entire payment succeeded.
			$aResponse['brq_statuscode'] = $aGroupResponse['brq_statuscode'];
		}

		$oStatus = ESL_Buckaroo_TransactionStatusFactory::createTransactionStatus($aResponse);
		$this->handleTransactionStatus($oStatus);

		return $oStatus;
	}

	/**
	 *
	 * @throws RuntimeException
	 * @param array $aTransaction
	 * @return null
	 */
	protected function handleTransactionStatus(ESL_Buckaroo_TransactionStatus $oTransactionStatus)
	{
		$sTransactionKey = $oTransactionStatus->getTransactionKey();

		if ($oTransactionStatus->isSuccess()) {
			$this->oCallbacks->handlePaymentSuccess($sTransactionKey);
		} elseif ($oTransactionStatus->isError()) {
			$this->oCallbacks->handlePaymentError($sTransactionKey);
		} elseif ($oTransactionStatus->isCancelled()) {
			$this->oCallbacks->handlePaymentCancelled($sTransactionKey);
		} elseif ($oTransactionStatus->isRejected()) {
			$this->oCallbacks->handlePaymentRejected($sTransactionKey);
		}
	}

	/**
	 * The Buckaroo gateway to communicate with
	 *
	 * In this case the NVP gateway
	 * 
	 * @return ESL_Buckaroo_Gateway
	 */
	protected function getGateway()
	{
		if (!$this->oGateway) {
			$this->oGateway = new ESL_Buckaroo_Gateway($this->getConfig());
		}
		return $this->oGateway;
	}

	/**
	 * The configuration as provided during instantiation
	 * 
	 * @return ESL_Buckaroo_Config
	 */
	protected function getConfig()
	{
		return $this->oConfig;
	}
}
?>
