<?php
/**
 * Implementation of the NVP gateway.
 *
 * @package Buckaroo
 * @version $Id: Gateway.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_Gateway
{
	/**
	 *
	 * @var string
	 */
	protected $sGatewayHost;

	/**
	 *
	 * @var string
	 */
	protected $sMerchantKey;

	/**
	 *
	 * @var string
	 */
	protected $sSecretKey;

	/**
	 *
	 * @var string
	 */
	protected $sLocale;

	/**
	 * 
	 * @param ESL_Buckaroo_Config $oConfig
	 */
	public function __construct(ESL_Buckaroo_Config $oConfig)
	{
		$this->sGatewayHost = $oConfig->getGatewayHost();
		$this->sMerchantKey = $oConfig->getMerchantKey();
		$this->sSecretKey = $oConfig->getSecretKey();
		$this->sLocale = $oConfig->getLocale();
	}

	/**
	 * Transaction Request Specification
	 *
	 * Get the available methods of payment and other details
	 *
	 * @param array $aData
	 * @return array
	 */
	public function transactionRequestSpecification()
	{
		$aData = array();
		$aRequestData = $this->completeRequest($aData);
		return $this->communicate('/nvp/?op=transactionrequestspecification', $aRequestData);
	}

	/**
	 * This request can be used to request a payment
	 *
	 * @throws ESL_Buckaroo_Exception_Payment
	 *
	 * @param array $aData
	 * @return string
	 */
	public function transactionRequest(ESL_Buckaroo_Request_TransactionRequest $oRequest)
	{
		$aData = $oRequest->getData();
		$aRequestData = $this->completeRequest($aData);
		return $this->communicate('/nvp/?op=transactionrequest', $aRequestData);
	}

	/**
	 * The TransactionStatus request can be used to retrieve the status of a previously created transaction
	 *
	 * @param array $aData
	 * @return array
	 */
	public function transactionStatus(ESL_Buckaroo_Request_TransactionStatus $oRequest)
	{
		$aData = $oRequest->getData();
		$aRequestData = $this->completeRequest($aData);
		return $this->communicate('/nvp/?op=transactionstatus', $aRequestData);
	}

	/**
	 *
	 * @param string $sRequestPath
	 * @param array $aRequestData
	 * @return array
	 */
	protected function communicate($sRequestPath, array $aRequestData)
	{
		$rCurl = curl_init('https://' . $this->sGatewayHost . $sRequestPath);
		curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($rCurl, CURLOPT_POST, 1);
		curl_setopt($rCurl, CURLOPT_POSTFIELDS, http_build_query($aRequestData));

		$sOutput = curl_exec($rCurl);
		$aCurlInfo = curl_getinfo($rCurl);
		curl_close($rCurl);

		if ($sOutput === false) {
			throw new ESL_Buckaroo_Exception_ServiceUnavailable("Buckaroo is unavailable");
		}
		if ($aCurlInfo['http_code'] != 200) {
			throw new ESL_Buckaroo_Exception_ServiceUnavailable("Invalid response code received from Buckaroo. Status code: {$aCurlInfo['http_code']}.");
		}

		$aResponse = $this->parseString($sOutput);

		// Verify signature
		if (isset($aResponse['brq_signature'])) {
			if (!$this->verifySignature($aResponse, $aResponse['brq_signature'])) {
				throw new RuntimeException("Invalid signature in response");
			}
		} elseif (isset($aResponse['BRQ_SIGNATURE'])) {
			if (!$this->verifySignature($aResponse, $aResponse['BRQ_SIGNATURE'])) {
				throw new RuntimeException("Invalid signature in response");
			}
		} else {
			throw new ESL_Buckaroo_Exception_ServiceUnavailable("Invalid response format received from Buckaroo. Signature is missing.");
		}

		// Convert all keys to lowercase for easier access
		return array_change_key_case($aResponse, CASE_LOWER);
	}

	/**
	 * Add signature and other required data to a request
	 *
	 * @param array $aRequestData
	 * @return array
	 */
	protected function completeRequest(array $aRequestData)
	{
		$aRequestData['brq_websiteKey'] = $this->sMerchantKey;
		$aRequestData['brq_latestversiononly'] = 'True';
		$aRequestData['brq_culture'] = $this->sLocale;
		$aRequestData['brq_signature'] = $this->signRequest($aRequestData);
		return $aRequestData;
	}

	/**
	 * Userland implementation of parse_str, since the native implementation is affected by max_input_vars, which we cannot change.
	 *
	 * Code taken from CakePHP https://github.com/cakephp/cakephp/blob/150c9fc6a3be0a86791202e76b5f0f2578c310fd/lib/Cake/Network/Http/HttpSocket.php#L767
	 *
	 * @param string $sQueryString
	 * @return array
	 */
	protected function parseString($sQueryString)
	{
		$aParsedQuery = array();
		$aItems = explode('&', $sQueryString);
		foreach ($aItems as $sItem) {
			if (strpos($sItem, '=') !== false) {
				list($sKey, $sValue) = explode('=', $sItem, 2);
				$sValue = urldecode($sValue);
			} else {
				$sKey = $sItem;
				$sValue = '';
			}

			$aParsedQuery[$sKey] = $sValue;
		}

		return $aParsedQuery;
	}

	/**
	 * Verify that a set of data and a given signature match.
	 *
	 * @param array $aData
	 * @param string $sVerificationSignature
	 * @return bool
	 */
	public function verifySignature(array $aData, $sVerificationSignature)
	{
		return ($this->signRequest($aData) == $sVerificationSignature);
	}

	/**
	 * Generate a signature for a list of fields.
	 *
	 * The signature calculation is as follows:
	 * 1. List all parameters prefixed with brq_, add_ or cust_, except brq_signature, and put them in the following format: brq_parametername=ParameterValue
	 *		Please note: When verifying a received signature, first url-decode all the field values. A signature is always calculated over the non-encoded values (i.e The
	 *		value “J.+de+Tester” should be decoded to “J. de Tester”).
	 * 2. Sort these parameters alphabetically on the parameter name (brq_amount comes before brq_websitekey).
	 *		Note: sorting must be case insensitive (brq_active comes before BRQ_AMOUNT) but casing in parameter names and values must be preserved.
	 * 3. Concatenate all the parameters, formatted as specified under 1, into one string. Do not use any separator or whitespace. Example: brq_amount=1.00brq_currency=EUR
	 * 4. Add the pre-shared secret key at the end of the string
	 * 5. Calculate a SHA-1 hash over this string. Return the hash in hexadecimal format.
	 *
	 * @param array $aData
	 * @return string Hexadecimal SHA-1 hash
	 */
	protected function signRequest(array $aData)
	{
		uksort(
			$aData,
			function($sKeyA, $sKeyB)
			{
				// Replace underscore with period, as period sorts before everything, while underscore does not
				return strcasecmp(str_replace('_', '.', $sKeyA), str_replace('_', '.', $sKeyB));
			}
		);

		// We need all fields starting with brq_, add_ or cust_, except brq_signature
		$sSignString = '';
		foreach ($aData as $sKey => $sValue) {
			if (!preg_match('/^(brq|add|cust)_/i', $sKey)) {
				continue;
			}
			if (strtolower($sKey) == 'brq_signature') {
				continue;
			}
			$sSignString .= "$sKey=$sValue";
		}

		// Append secret key
		$sSignString .= $this->sSecretKey;

		return sha1($sSignString);
	}
}
?>