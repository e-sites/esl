<?php
/**
 * Perform a status lookup for one or multiple (max. 100) tweets.
 *
 * To lookup tweets by protected users, those users must be followed.
 *
 * This API is rate limited to 180 requests per 15 minutes.
 *
 * @see https://dev.twitter.com/rest/reference/get/statuses/lookup
 * 
 * @package Twitter
 * @version $Id$
 */
class ESL_Twitter_Request_StatusLookup extends ESL_Twitter_Request
{
	const REQUEST_PATH = 'statuses/lookup';
	const REQUEST_METHOD = 'GET';

	/**
	 * The text of your status update, typically up to 140 characters.
	 * 
	 * There are some special commands in this field to be aware of. For instance, preceding a message with "D " or "M " and following it with a screen name can create a
	 * direct message to that user if the relationship allows for it.
	 *
	 * @param array $aStatussess
	 */
	public function __construct($aStatusses)
	{
		if (count($aStatusses) > 100) {
			throw new InvalidArgumentException("We can lookup 100 tweets at most in one API call.");
		}
		$this->aParameters['id'] = implode(',', $aStatusses);
	}
}
?>