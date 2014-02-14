<?php
/**
 * Simple oAuth v1.0 class to sign requests
 *
 * @see https://github.com/jrconlin/oauthsimple
 * 
 * @package Twitter
 * @version $Id: Oauth.php 601 2013-10-15 13:52:03Z fpruis $
 */
class ESL_Twitter_Oauth
{
	/**
	 * Consumer key
	 *
	 * @var string
	 */
	protected $sConsumerKey;

	/**
	 * Consumer secret
	 * 
	 * @var string
	 */
	protected $sConsumerSecret;

	/**
	 * Access token
	 * 
	 * @var string
	 */
	protected $sAccessToken;

	/**
	 * Access token secret
	 * 
	 * @var string
	 */
	protected $sAccessTokenSecret;

	/**
	 *
	 * @param $sConsumerKey Consumer key
	 * @param $sConsumerSecret Consumer secret
	 * @param $sAccessToken Access token
	 * @param $sAccessTokenSecret Access token secret
	 */
	public function __construct($sConsumerKey, $sConsumerSecret, $sAccessToken, $sAccessTokenSecret)
	{
		$this->sConsumerKey = $sConsumerKey;
		$this->sConsumerSecret = $sConsumerSecret;
		$this->sAccessToken = $sAccessToken;
		$this->sAccessTokenSecret = $sAccessTokenSecret;
	}

	/**
	 * Build a URL with all parameters escaped and a signature added
	 *
	 * @param string $sRequestPath
	 * @param string $sRequestMethod (GET|POST)
	 * @param array $aQuerystring
	 * @return string Signed URL
	 */
	public function getSignedUrl($sRequestPath, $sRequestMethod, array $aQuerystring)
	{
		// Extend querystring with oAuth details
		$aQuerystring['oauth_version'] = '1.0a';
		$aQuerystring['oauth_nonce'] = $this->generateNonce();
		$aQuerystring['oauth_timestamp'] = time();
		$aQuerystring['oauth_consumer_key'] = $this->sConsumerKey;
		$aQuerystring['oauth_token'] = $this->sAccessToken;
		$aQuerystring['oauth_signature_method'] = 'HMAC-SHA1';

		// Generate signature for querystring and append
		$aQuerystring['oauth_signature'] = $this->generateSignature($sRequestPath, $sRequestMethod, $aQuerystring);

		// Return full URL with signed querystring
		return $sRequestPath . '?' . $this->formatParameters($aQuerystring);
	}

	/**
	 * Generate random 'nonce'
	 * 
	 * @return string
	 */
	protected function generateNonce()
	{
		if (function_exists('openssl_random_pseudo_bytes')) {
			$sNonce = bin2hex(openssl_random_pseudo_bytes(6));
		} else {
			$sNonce = sprintf(
				'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				// 32 bits for "time_low"
				mt_rand(0, 0xffff), mt_rand(0, 0xffff),
				// 16 bits for "time_mid"
				mt_rand(0, 0xffff),
				// 16 bits for "time_hi_and_version", four most significant bits holds version number 4
				mt_rand(0, 0x0fff) | 0x4000,
				// 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low", two most significant bits holds zero and one for variant DCE1.1
				mt_rand(0, 0x3fff) | 0x8000,
				// 48 bits for "node"
				mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
			);
		}

		return $sNonce;
	}

	/**
	 * Generate signature for parameters using consument_secret
	 *
	 * @return string base64 safe signature
	 */
	protected function generateSignature($sRequestUri, $sRequestMethod, array $aQuerystring)
	{
		$sData = rawurlencode($sRequestMethod) . '&' . rawurlencode($sRequestUri) . '&' . rawurlencode($this->formatParameters($aQuerystring));
		$sKey = rawurlencode($this->sConsumerSecret) . '&' . rawurlencode($this->sAccessTokenSecret);
		return base64_encode(hash_hmac('sha1', $sData, $sKey, true));
	}

	/**
	 * Format parameters to be signed
	 *
	 * @return string Querystring
	 */
	protected function formatParameters(array $aParameters)
	{
		// Sort by parameter name
		ksort($aParameters);

		return http_build_query($aParameters);
	}
}
?>