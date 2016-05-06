<?php
/**
 * Base for all requests
 *
 * @see https://dev.twitter.com/docs/api/1.1 API Reference
 * 
 * @package Twitter
 * @version $Id$
 */
abstract class ESL_Twitter_Request
{
	/**
	 * Parameters to be send in the request
	 * 
	 * @var array
	 */
	protected $aParameters = array();

	/**
	 * Return the parameters set in the request
	 *
	 * @return array
	 */
	public function getRequestParameters()
	{
		return $this->aParameters;
	}
}
?>