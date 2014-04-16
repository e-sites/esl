<?php
/**
 * Twitter client
 *
 * Read timelines, search hashtags, update statusses and change profile pictures
 *
 * @see https://dev.twitter.com/docs/api/1.1
 *
 * @package Twitter
 * @version $Id: Twitter.php 601 2013-10-15 13:52:03Z fpruis $
 */
class ESL_Twitter
{
	/**
	 * The API endpoint we are using
	 */
	const API_ENDPOINT = 'https://api.twitter.com/1.1/';

	/**
	 *
	 * @var ESL_Twitter_Oauth
	 */
	protected $oAuth;

	/**
	 * Create a new ESL_Twitter instance.
	 *
	 * Provide the consumer key, secret, access token and access token secret as given by Twitter to login into an existing account
	 *
	 * @see https://dev.twitter.com/apps Twitter Applications
	 *
	 * @param string $sConsumerKey Twitter API Consumer key
	 * @param string $sConsumerSecret Twitter API Consumer secret
	 * @param string $sAccessToken Twitter API Access token
	 * @param string $sAccessTokenSecret Twitter API Access token secret
	 * @return ESL_Twitter
	 */
	static public function factory($sConsumerKey, $sConsumerSecret, $sAccessToken, $sAccessTokenSecret)
	{
		return new ESL_Twitter(new ESL_Twitter_Oauth($sConsumerKey, $sConsumerSecret, $sAccessToken, $sAccessTokenSecret));
	}

	/**
	 * Provide a ESL_Twitter_Oauth instance to authenticate as a Twitter user
	 * 
	 * @param ESL_Twitter_Oauth $oAuth
	 */
	public function __construct(ESL_Twitter_Oauth $oAuth)
	{
		$this->oAuth = $oAuth;
	}

	/**
	 * Return ESL_Twitter_Oauth instance
	 * 
	 * @return ESL_Twitter_Oauth
	 */
	protected function getOauth()
	{
		return $this->oAuth;
	}

	/**
	 * Send request to Twitter API and return result
	 *
	 * @throws RuntimeException
	 *
	 * @param string $sPath
	 * @param ESL_Twitter_Request $oRequest
	 * @param bool $bUsePostMethod
	 * @return stdClass|array
	 */
	protected function doRequest(ESL_Twitter_Request $oRequest)
	{
		$sRequestPath = self::API_ENDPOINT . $oRequest::REQUEST_PATH . '.json';
		$sRequestMethod = $oRequest::REQUEST_METHOD;
		$aRequestParameters = $oRequest->getRequestParameters();

		//	Have the request signed. For GET requests, we need to add the parameters to the URL. For other requests, we shouldn't.
		$sSignedUrl = $this->getOAuth()->getSignedUrl(
			$sRequestPath,
			($sRequestMethod == 'GET' ? 'GET' : 'POST'),
			($sRequestMethod == 'GET' ? $aRequestParameters : array())
		);

		$rCurl = curl_init($sSignedUrl);
		curl_setopt($rCurl, CURLOPT_HEADER, 0);
		curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($rCurl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($rCurl, CURLOPT_TIMEOUT, 30);
		curl_setopt($rCurl, CURLOPT_MAXREDIRS, 3);

		//	If it is a POST, tell curl that and set the parameters.
		if ($sRequestMethod != 'GET') {
			curl_setopt($rCurl, CURLOPT_POST, true);
			curl_setopt($rCurl, CURLOPT_POSTFIELDS, $aRequestParameters);
		}

		//	Do the request.
		$sResult = curl_exec($rCurl);

		//	If there is an error, 
		if (curl_errno($rCurl) != CURLE_OK) {
			throw new RuntimeException(curl_error($rCurl));
		}

		$aCurlInfo = curl_getinfo($rCurl);
		curl_close($rCurl);
		unset($rCurl);

		$oResponse = json_decode($sResult);

		if (!empty($oResponse->errors)) {
			// Errors from twitter
			$oError = reset($oResponse->errors);
			throw new RuntimeException($oError->message, $oError->code);
		}

		if ($aCurlInfo['http_code'] >= 400) {
			throw new RuntimeException($oResponse, $aCurlInfo['http_code']);
		}

		return $oResponse;
	}

	/**
	 * Updates the authenticating user's current status, also known as tweeting.
	 *
	 * For each update attempt, the update text is compared with the authenticating user's recent tweets. Any attempt that would result in duplication will be blocked,
	 * resulting in a exception. Therefore, a user cannot submit the same status twice in a row.
	 *
	 * While not rate limited by the API a user is limited in the number of tweets they can create at a time. If the number of updates posted by the user reaches the
	 * current allowed limit this method will throw an exception.
	 *
	 * @throws RuntimeException
	 *
	 * @param ESL_Twitter_Request_StatusUpdate $oRequest
	 * @return ESL_Twitter_Response_StatusUpdate
	 */
	public function postStatusUpdate(ESL_Twitter_Request_StatusUpdate $oRequest)
	{
		return new ESL_Twitter_Response_StatusUpdate($this->doRequest($oRequest));
	}

	/**
	 * Updates the authenticating user's profile image.
	 * 
	 * This method asynchronously processes the uploaded file before updating the user's profile image URL. You can either update your local cache the next time you request 
	 * the user's information.
	 *
	 * @throws RuntimeException
	 *
	 * @param ESL_Twitter_Request_UpdateProfileImage $oRequest
	 * @return null
	 */
	public function updateProfileImage(ESL_Twitter_Request_UpdateProfileImage $oRequest)
	{
		$this->doRequest($oRequest);
	}


	/**
	 * Returns a collection of the most recent Tweets posted by the user indicated by the screen_name or user_id parameters, or the authenticated user if none is given
	 *
	 * User timelines belonging to protected users may only be requested when the authenticated user either "owns" the timeline or is an approved follower of the owner.
	 *
	 * The timeline returned is the equivalent of the one seen when you view a user's profile on twitter.com.
	 *
	 * This method can only return up to 3200 of a user's most recent Tweets. Native retweets of other statuses by the user is included in this total, regardless of
	 * whether include_rts is set to false when requesting this resource.
	 *
	 * @see https://dev.twitter.com/docs/working-with-timelines Working with Timelines
	 * @throws RuntimeException
	 *
	 * @param ESL_Twitter_Request_UserTimeline $oRequest
	 * @return ESL_Twitter_Response_UserTimeline
	 */
	public function getUserTimeline(ESL_Twitter_Request_UserTimeline $oRequest)
	{
		return new ESL_Twitter_Response_UserTimeline($this->doRequest($oRequest));
	}

	/**
	 * Returns a collection of relevant Tweets matching a specified query.
	 *
	 * Please note that Twitter's search service and, by extension, the Search API is not meant to be an exhaustive source of Tweets. Not all Tweets will be indexed or made
	 * available via the search interface.
	 *
	 * @see https://dev.twitter.com/docs/using-search Using the Twitter Search API
	 * @see https://dev.twitter.com/docs/working-with-timelines Working with Timelines
	 * @throws RuntimeException
	 *
	 * @param ESL_Twitter_Request_Search $oRequest
	 * @return ESL_Twitter_Response_Search
	 */
	public function search(ESL_Twitter_Request_Search $oRequest)
	{
		return new ESL_Twitter_Response_Search($this->doRequest($oRequest));
	}

}
?>