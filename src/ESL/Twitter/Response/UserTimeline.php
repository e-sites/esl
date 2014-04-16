<?php
/**
 * Collection of tweets gathered from a user it's timeline
 * 
 * @package Twitter
 * @version $Id: UserTimeline.php 601 2013-10-15 13:52:03Z fpruis $
 */
class ESL_Twitter_Response_UserTimeline implements IteratorAggregate
{
	/**
	 *
	 * @var ESL_Twitter_Tweet[]
	 */
	protected $aTweets;

	/**
	 * @param stdClass[] $aTweets
	 */
	public function __construct(array $aTweets)
	{
		$this->aTweets = array();
		foreach ($aTweets AS $oTweet) {
			$this->aTweets[] = new ESL_Twitter_Tweet($oTweet);
		}
	}

	/**
	 * Implements IteratorAggregate.
	 *
	 * Use this object in a foreach() to iterate over all tweets.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->aTweets);
	}

	/**
	 * Get array with all tweets
	 *
	 * @return ESL_Twitter_Tweet[]
	 */
	public function getTweets()
	{
		return $this->aTweets;
	}
}
?>