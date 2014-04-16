<?php
/**
 * Base for all requests
 *
 * @see https://dev.twitter.com/docs/api/1.1 API Reference
 * 
 * @package Twitter
 * @version $Id: Request.php 601 2013-10-15 13:52:03Z fpruis $
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