<?php
/**
 * Collection of tweets gathered from a user it's timeline
 * 
 * @package Twitter
 * @version $Id: UserTimeline.php 832 2014-12-24 08:28:39Z fpruis $
 */
class ESL_Twitter_Response_UserTimeline implements IteratorAggregate, Countable
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

	/**
	 * Count the number of tweets that was retreived from the timeline request.
	 *
	 * @internal Countable
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->aTweets);
	}
}
?>