<?php
/**
 * A user
 *
 * @see https://dev.twitter.com/docs/platform-objects/users
 * 
 * @package Twitter
 * @version $Id: User.php 631 2013-11-06 12:14:43Z fpruis $
 */
class ESL_Twitter_User
{
	/**
	 *
	 * @var string
	 */
	protected $sId;

	/**
	 *
	 * @var string
	 */
	protected $sName;

	/**
	 *
	 * @var string
	 */
	protected $sScreenName;

	/**
	 *
	 * @var string
	 */
	protected $sProfileImageUrl;

	/**
	 *
	 * @var string
	 */
	protected $sProfileImageHttpsUrl;

	/**
	 *
	 * @param stdClass $oStruct
	 */
	public function __construct(stdClass $oStruct)
	{
		$this->sId = $oStruct->id_str;
		$this->sName = $oStruct->name;
		$this->sScreenName = $oStruct->screen_name;
		$this->sProfileImageUrl = $oStruct->profile_image_url;
		$this->sProfileImageHttpsUrl = $oStruct->profile_image_url_https;
	}

	/**
	 *
	 * @return string
	 */
	public function getUserId()
	{
		return $this->sId;
	}

	/**
	 * Full name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->sName;
	}

	/**
	 * Accountname
	 * 
	 * @return string
	 */
	public function getScreenName()
	{
		return $this->sScreenName;
	}

	/**
	 *
	 * @param bool $bHttps Optional. To return https addresss. Default falses
	 * @return string
	 */
	public function getProfileImage($bHttps = false)
	{
		return ($bHttps ? $this->sProfileImageHttpsUrl : $this->sProfileImageUrl);
	}
}
?>