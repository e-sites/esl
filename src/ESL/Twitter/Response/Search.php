<?php
/**
 * Collection of tweets as a search result
 *
 * @package Twitter
 * @version $Id$
 */
class ESL_Twitter_Response_Search implements IteratorAggregate
{
	/**
	 *
	 * @var array
	 */
	protected $aTweets;

	/**
	 *
	 * @param stdClass $oStruct
	 */
	public function __construct(stdClass $oStruct)
	{
		$this->aTweets = array();
		foreach ($oStruct->statuses AS $oTweet) {
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
	 * @return ESL_Twitter_Tweet[]
	 */
	public function getTweets()
	{
		return $this->aTweets;
	}
}
?>