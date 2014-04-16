<?php
/**
 * Updates the authenticating user's current status, also known as tweeting. 
 *
 * For each update attempt, the update text is compared with the authenticating user's recent tweets. Any attempt that would result in duplication will be blocked,
 * resulting in a 403 error. Therefore, a user cannot submit the same status twice in a row.
 *
 * While not rate limited by the API a user is limited in the number of tweets they can create at a time. If the number of updates posted by the user reaches the
 * current allowed limit this method will return an HTTP 403 error.
 *
 * @see https://dev.twitter.com/docs/api/1.1/post/statuses/update
 * 
 * @package Twitter
 * @version $Id: StatusUpdate.php 601 2013-10-15 13:52:03Z fpruis $
 */
class ESL_Twitter_Request_StatusUpdate extends ESL_Twitter_Request
{
	const REQUEST_PATH = 'statuses/update';
	const REQUEST_METHOD = 'POST';

	/**
	 * The text of your status update, typically up to 140 characters.
	 * 
	 * There are some special commands in this field to be aware of. For instance, preceding a message with "D " or "M " and following it with a screen name can create a
	 * direct message to that user if the relationship allows for it.
	 *
	 * @param string $sStatus
	 */
	public function __construct($sStatus)
	{
		$this->aParameters['status'] = $sStatus;
	}
}
?>