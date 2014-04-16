<?php
/**
 * An url-entity in a tweet
 *
 * @see https://dev.twitter.com/docs/tweet-entities
 *
 * @package Twitter
 * @version $Id: Url.php 662 2014-02-14 14:17:32Z fpruis $
 */
class ESL_Twitter_Entity_Url
{
	/**
	 * The URL that was extracted
	 * 
	 * @var string
	 */
	protected $sText;

	/**
	 * Not a URL but a string to display instead of the URL
	 * 
	 * @var string
	 */
	protected $sTextDisplay;

	/**
	 * The fully resolved URL
	 * 
	 * @var string
	 */
	protected $sUrl;

	/**
	 *
	 * @param stdClass $oStruct
	 */
	public function __construct(stdClass $oStruct)
	{
		$this->sText = $oStruct->url;
		$this->sTextDisplay = (!empty($oStruct->display_url) ? $oStruct->display_url : $oStruct->url);
		$this->sUrl = (!empty($oStruct->expanded_url) ? $oStruct->expanded_url : $oStruct->url);
	}

	/**
	 * Text how it was extracted from the tweet
	 *
	 * Use in plain text
	 *
	 * @return string
	 */
	public function getText()
	{
		return $this->sText;
	}

	/**
	 * Not a URL but a string to display instead of the URL
	 *
	 * Use as anchor text
	 *
	 * @return string
	 */
	public function getTextDisplay()
	{
		return $this->sTextDisplay;
	}

	/**
	 * The fully resolved URL
	 *
	 * Use as anchor destination
	 */
	public function getUrl()
	{
		return $this->sUrl;
	}
}
?>