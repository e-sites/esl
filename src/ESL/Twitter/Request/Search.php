<?php
/**
 * Returns a collection of relevant Tweets matching a specified query.
 *
 * Please note that Twitter's search service and, by extension, the Search API is not meant to be an exhaustive source of Tweets. Not all Tweets will be indexed
 * or made available via the search interface.
 *
 * @see https://dev.twitter.com/docs/api/1.1/get/search/tweets
 * @see https://dev.twitter.com/docs/using-search Using the Twitter Search AP
 * @see https://dev.twitter.com/docs/working-with-timelines Working with Timelines
 *
 * @package Twitter
 * @version $Id$
 */
class ESL_Twitter_Request_Search extends ESL_Twitter_Request
{
	const REQUEST_PATH = 'search/tweets';
	const REQUEST_METHOD = 'GET';

	const RESULTTYPE_MIXED = 'mixed';
	const RESULTTYPE_RECENT = 'recent';
	const RESULTTYPE_POPULAIR = 'populair';

	/**
	 * A UTF-8 search query of 1,000 characters maximum, including operators. Queries may additionally be limited by complexity.
	 * 
	 * @param string $sQuery.
	 */
	public function __construct($sQuery)
	{
		$this->aParameters['q'] = $sQuery;
	}

	/**
	 * Restricts tweets to the given language, given by an ISO 639-1 code.
	 *
	 * Language detection is best-effort.
	 *
	 * @param string $sLang
	 */
	public function setLang($sLang)
	{
		$this->aParameters['lang'] = $sLang;
	}

	/**
	 * Specifies what type of search results you would prefer to receive. The current default is "mixed"
	 * 
	 * Valid values include:
	 *  RESULTTYPE_MIXED: Include both popular and real time results in the response.
	 *  RESULTTYPE_RECENT: return only the most recent results in the response
	 *  RESULTTYPE_POPULAR: return only the most popular results in the response.
	 *
	 * @param string $sResultType
	 */
	public function setResultType($sResultType)
	{
		$this->aParameters['result_type'] = $sResultType;
	}

	/**
	 * The number of tweets to return per page, up to a maximum of 100.
	 *
	 * Defaults to 15. This was formerly the "rpp" parameter in the old Search API.
	 *
	 * @param int $iCount
	 */
	public function setCount($iCount)
	{
		$this->aParameters['count'] = $iCount;
	}

	/**
	 * Returns tweets generated before the given date.
	 *
	 * Keep in mind that the search index may not go back as far as the date you specify here.
	 * 
	 * @param DateTime $oUntil
	 */
	public function setUntil(DateTime $oUntil)
	{
		$this->aParameters['until'] = $oUntil->format('Y-m-d');
	}

	/**
	 * Returns results with an ID greater than (that is, more recent than) the specified ID.
	 *
	 * There are limits to the number of Tweets which can be accessed through the API. If the limit of Tweets has occured since the since_id, the since_id will be
	 * forced to the oldest ID available.
	 *
	 * @param string $sSinceId
	 */
	public function setSinceId($sSinceId)
	{
		$this->aParameters['since_id'] = $sSinceId;
	}

	/**
	 * Returns results with an ID less than (that is, older than) or equal to the specified ID.
	 *
	 * @param string $sMaxId
	 */
	public function setMaxId($sMaxId)
	{
		$this->aParameters['max_id'] = $sMaxId;
	}
}
?>