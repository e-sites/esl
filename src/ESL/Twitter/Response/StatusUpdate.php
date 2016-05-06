<?php
/**
 * The tweet that was stored
 * 
 * @package Twitter
 * @version $Id$
 */
class ESL_Twitter_Response_StatusUpdate
{
	/**
	 *
	 * @var ESL_Twitter_Tweet
	 */
	protected $oTweet;

	/**
	 *
	 * @param stdClass $oStruct
	 */
	public function __construct(stdClass $oStruct)
	{
		$this->oTweet = new ESL_Twitter_Tweet($oStruct);
	}

	/**
	 * Get tweet
	 *
	 * @return ESL_Twitter_Tweet
	 */
	public function getTweet()
	{
		return $this->oTweet;
	}
}
?>