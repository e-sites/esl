<?php
/**
 * A tweet
 * 
 * @see https://dev.twitter.com/docs/platform-objects/tweets
 * 
 * @package Twitter
 * @version $Id: Tweet.php 601 2013-10-15 13:52:03Z fpruis $
 */
class ESL_Twitter_Tweet
{
	/**
	 *
	 * @var string
	 */
	protected $sId;

	/***
	 *
	 * @var DateTime
	 */
	protected $oDate;

	/**
	 *
	 * @var string
	 */
	protected $sText;

	/**
	 *
	 * @var ESL_Twitter_User
	 */
	protected $oUser;

	/**
	 *
	 * @var array
	 */
	protected $aHashtags;

	/**
	 *
	 * @var array
	 */
	protected $aUrls;

	/**
	 * Detailed information for a url
	 * 
	 * @var ESL_Twitter_Entity_Url[]
	 */
	protected $aEntityUrls;

	/**
	 *
	 * @var array
	 */
	protected $aMentions;

	/**
	 *
	 * @var ESL_Twitter_Entity_Media[]
	 */
	protected $aEntityMedia;

	/**
	 *
	 * @var int
	 */
	protected $iRetweetCount;

	/**
	 * Whether tweet is a retweet of someone else
	 * 
	 * @var bool
	 */
	protected $bIsRetweet;

	/**
	 * Whether tweet is a reply to someone else
	 *
	 * @var bool
	 */
	protected $bIsReply;

	public function __construct(stdClass $oStruct)
	{
		$this->sId = $oStruct->id_str;
		$this->oDate = new DateTime($oStruct->created_at);
		$this->oUser = new ESL_Twitter_User($oStruct->user);

		$this->aHashtags = array();
		$this->aUrls = array();
		$this->aEntityUrls = array();
		$this->aMentions = array();
		$this->aEntityMedia = array();

		if (!empty($oStruct->retweeted_status)) {
			// Get urls/media/mentions from original tweet
			$this->parseTweetEntities($oStruct->retweeted_status->entities);
			// Manually add mention to original tweep
			$this->aMentions[] = $oStruct->retweeted_status->user->screen_name;

			// Set custom tweet with retweet header and full, original tweet
			$this->sText = 'RT @' . $oStruct->retweeted_status->user->screen_name . ': ' . $oStruct->retweeted_status->text;
		} else {
			$this->parseTweetEntities($oStruct->entities);
			$this->sText = $oStruct->text;
		}

		$this->iRetweetCount = $oStruct->retweet_count;

		$this->bIsRetweet = !empty($oStruct->retweeted_status);
		$this->bIsReply = !empty($oStruct->in_reply_to_status_id_str);
	}

	/**
	 * Parse entities from tweet text into seperate arrays
	 * 
	 * @param stdClass $oEntities
	 */
	protected function parseTweetEntities(stdClass $oEntities)
	{
		foreach ($oEntities->hashtags as $oTag) {
			$this->aHashtags[] = $oTag->text;
		}

		foreach ($oEntities->urls as $oUrl) {
			// Simple list of url's used in tweet
			$this->aUrls[] = $oUrl->url;
			$this->aEntityUrls[] = new ESL_Twitter_Entity_Url($oUrl);
		}

		foreach ($oEntities->user_mentions as $oMention) {
			$this->aMentions[] = $oMention->screen_name;
		}

		if (isset($oEntities->media)) {
			foreach ($oEntities->media as $oMedia) {
				$this->aEntityMedia[] = new ESL_Twitter_Entity_Media($oMedia);
			}
		}
	}

	/**
	 * Return tweet with rich text
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getText();
	}

	/**
	 * Returns the date the tweet was posted
	 * 
	 * @return DateTime
	 */
	public function getCreatedDate()
	{
		return $this->oDate;
	}

	/**
	 * Instead of the date, return the age of a tweet
	 *
	 * For example; 12 seconden, 12 uur, 3 dagen
	 * For tweets older than 7 days the date is given
	 *
	 * @param bool $bShort Default false Return "2d" instead of "2 days"
	 * @return string
	 */
	public function getAge($bShort = false)
	{
		$aMonths = array(
			1 => array('januari', 'jan'),
			2 => array('februari', 'feb'),
			3 => array('maart', 'mrt'),
			4 => array('april', 'apr'),
			5 => array('mei', 'mei'),
			6 => array('juni', 'jun'),
			7 => array('juli', 'jul'),
			8 => array('augustus', 'aug'),
			9 => array('september', 'sep'),
			10 => array('oktober', 'okt'),
			11 => array('november', 'nov'),
			12 => array('december', 'dec')
		);

		$oDateNow = new DateTime();
		$oDateTweet = $this->getCreatedDate();
		$oAge = $oDateNow->diff($oDateTweet);

		// Human readable date
		if ($oAge->y > 0) {
			$sAgeHuman = $oDateTweet->format('j') . ' ' . $aMonths[$oDateTweet->format('n')][$bShort] . ' ' . $oDateTweet->format('Y');
		} elseif ($oAge->days > 7) {
			$sAgeHuman = $oDateTweet->format('j') . ' ' . $aMonths[$oDateTweet->format('n')][$bShort];
		} elseif ($oAge->days > 0) {
			$sAgeHuman = $oAge->days . ($bShort ? 'd' : ' dagen');
		} elseif ($oAge->h > 0) {
			$sAgeHuman = $oAge->h . ($bShort ? 'u' : ' uur');
		} elseif ($oAge->i > 0) {
			$sAgeHuman = $oAge->i . ($bShort ? 'm' : ' minuten');
		} else {
			$sAgeHuman = $oAge->s . ($bShort ? 's' : ' seconden');
		}

		return $sAgeHuman;
	}

	/**
	 * Returns list of hashtags used in the tweet
	 *
	 * This list excludes the hashtag (#) itself
	 * 
	 * @return array
	 */
	public function getHashtags()
	{
		return $this->aHashtags;
	}

	/**
	 * Returns list of url's used in the tweet
	 *
	 * The url's might be shortened by Twitter, and not the same as used in the post or displayed on the Twitter site itself
	 *
	 * @return array
	 */
	public function getUrls()
	{
		return $this->aUrls;
	}

	/**
	 * Returns list of screen_name's from users mentioned in the tweet
	 *
	 * @return array
	 */
	public function getMentions()
	{
		return $this->aMentions;
	}

	/**
	 * Return details about the user who posted the tweet
	 * 
	 * @return ESL_Twitter_User
	 */
	public function getUser()
	{
		return $this->oUser;
	}

	/**
	 * Returns the tweet ID
	 * 
	 * @return string
	 */
	public function getId()
	{
		return $this->sId;
	}

	/**
	 * Returns the tweet in rich text, where all hashtags, urls and mentions are HTML-links
	 *
	 * HTML entities inside the status have been escaped by Twitter already
	 * 
	 * @return string
	 */
	public function getText()
	{
		$sText = $this->getPlainText();

		foreach ($this->getHashtags() AS $sTag) {
			$sText = str_replace('#' . $sTag, '<a href="https://twitter.com/search?q=%23' . $sTag. '" class="twitter-tag" rel="external">#' . $sTag . '</a>', $sText);
		}

		foreach ($this->getEntityMedia() AS $oMedia) {
			$sMediaText = $oMedia->getText();
			$sText = str_replace($sMediaText, '<a href="' . $sMediaText . '" class="twitter-media" rel="external">' . $oMedia->getTextDisplay() . '</a>', $sText);
		}

		foreach ($this->getEntityUrls() AS $oUrl) {
			$sText = str_replace($oUrl->getText(), '<a href="' . $oUrl->getUrl() . '" class="twitter-url" rel="external">' . $oUrl->getTextDisplay() . '</a>', $sText);
		}

		foreach ($this->getMentions() AS $sMention) {
			$sText = str_ireplace('@' . $sMention, '<a href="https://twitter.com/' . $sMention . '" class="twitter-user" rel="external">@' . $sMention . '</a>', $sText);
		}

		return $sText;
	}

	/**
	 * Returns the tweet status text as plaintext
	 *
	 * HTML entities inside the status have been escaped by Twitter already
	 *
	 * To get a rich version of the text where hashtags, url's and mentions are HTML-links to their correspondenting destinations use getText()
	 *
	 * @return string
	 */
	public function getPlainText()
	{
		return $this->sText;
	}

	/**
	 * Whether tweet is a retweet of someone else
	 * 
	 * @return bool
	 */
	public function isRetweet()
	{
		return $this->bIsRetweet;
	}

	/**
	 * Whether tweet is a reply to someone else
	 * 
	 * @return bool
	 */
	public function isReply()
	{
		return $this->bIsReply;
	}

	/**
	 * Return Url-entities in this tweet
	 * 
	 * @return ESL_Twitter_Entity_Url[]
	 */
	public function getEntityUrls()
	{
		return $this->aEntityUrls;
	}

	/**
	 * Return Media-entities in this tweet
	 * 
	 * @return ESL_Twitter_Entity_Media[]
	 */
	public function getEntityMedia()
	{
		return $this->aEntityMedia;
	}

}
?>