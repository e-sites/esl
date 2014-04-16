<?php
/**
 * Returns a collection of the most recent Tweets posted by the user indicated by the screen_name or user_id parameters.
 * 
 * User timelines belonging to protected users may only be requested when the authenticated user either "owns" the timeline or is an approved follower of the owner.
 * 
 * The timeline returned is the equivalent of the one seen when you view a user's profile on twitter.com.
 * 
 * This method can only return up to 3.200 of a user's most recent Tweets. Native retweets of other statuses by the user is included in this total, regardless of 
 * whether include_rts is set to false when requesting this resource.
 *
 * @see https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
 * @see https://dev.twitter.com/docs/working-with-timelines Working with Timelines
 * 
 * @package Twitter
 * @version $Id: UserTimeline.php 601 2013-10-15 13:52:03Z fpruis $
 */
class ESL_Twitter_Request_UserTimeline extends ESL_Twitter_Request
{
	const REQUEST_PATH = 'statuses/user_timeline';
	const REQUEST_METHOD = 'GET';

	public function __construct()
	{
		// By default exclude retweets and replies
		$this->aParameters['include_rts'] = '0';
		$this->aParameters['exclude_replies'] = '1';
	}

	/**
	 * The ID of the user for whom to return results for.
	 *
	 * @param int $sUserId
	 */
	public function setUserId($sUserId)
	{
		$this->aParameters['user_id'] = $sUserId;
	}

	/**
	 * The screen name of the user for whom to return results for.
	 * 
	 * @param string $sScreenName
	 */
	public function setScreenName($sScreenName)
	{
		$this->aParameters['screen_name'] = $sScreenName;
	}

	/**
	 * Returns results with an ID greater than (that is, more recent than) the specified ID.
	 *
	 * There are limits to the number of Tweets which can be accessed through the API.
	 * If the limit of Tweets has occured since the since_id, the since_id will be forced to the oldest ID available.
	 *
	 * @param int $sSinceId
	 */
	public function setSinceId($sSinceId)
	{
		$this->aParameters['since_id'] = $sSinceId;
	}

	/**
	 * Specifies the number of tweets to try and retrieve, up to a maximum of 200 per distinct request.
	 *
	 * The value of count is best thought of as a limit to the number of tweets to return because suspended or deleted content is removed after the count has been applied.
	 * We include retweets in the count, even if include_rts is not supplied. It is recommended you always send include_rts=1 when using this API method.
	 *
	 * @param int $iCount
	 */
	public function setCount($iCount)
	{
		$this->aParameters['count'] = $iCount;
	}

	/**
	 * Returns results with an ID less than (that is, older than) or equal to the specified ID.
	 *
	 * @param int $sMaxId
	 */
	public function setMaxId($sMaxId)
	{
		$this->aParameters['max_id'] = $sMaxId;
	}

	/**
	 * Include replies
	 *
	 * Using excludeReplies with the count parameter will mean you will receive up-to count tweets — this is because the count parameter retrieves that many tweets
	 * before filtering out retweets and replies.
	 */
	public function includeReplies()
	{
		$this->aParameters['exclude_replies'] = '0';
	}

	/**
	 * Include retweets
	 *
	 * When set to false, the timeline will strip any native retweets (though they will still count toward both the maximal length of the timeline and the slice selected
	 * by the count parameter).
	 */
	public function includeRetweets()
	{
		$this->aParameters['include_rts'] = '1';
	}

}
?>