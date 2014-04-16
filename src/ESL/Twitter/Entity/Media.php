<?php
/**
 * A media-entity (image/video) in a tweet
 *
 * @see https://dev.twitter.com/docs/tweet-entities
 *
 * @package Twitter
 * @version $Id: Media.php 662 2014-02-14 14:17:32Z fpruis $
 */
class ESL_Twitter_Entity_Media
{
	const SIZE_THUMB  = 'thumb';    // 150x150
	const SIZE_SMALL  = 'small';    // 340x340
	const SIZE_MEDIUM = 'medium';   // 600x600
	const SIZE_LARGE  = 'large';    // 1024x1024

	/**
	 *
	 * @var string
	 */
	protected $sId;

	/***
	 *
	 * @var string
	 */
	protected $sImage;

	/**
	 *
	 * @var string
	 */
	protected $sImageSecure;

	/**
	 * The URL how it was extracted
	 * 
	 * @var string
	 */
	protected $sText;

	/**
	 *
	 * @var string
	 */
	protected $sTextDisplay;

	/**
	 *
	 * @var array
	 */
	protected $aSizes;

	/**
	 *
	 * @param stdClass $oStruct
	 */
	public function __construct(stdClass $oStruct)
	{
		$this->sId = $oStruct->id_str;
		$this->sImage = $oStruct->media_url;
		$this->sImageSecure = $oStruct->media_url_https;

		$this->sText = $oStruct->url;
		$this->sTextDisplay = (!empty($oStruct->display_url) ? $oStruct->display_url : $oStruct->url);

		foreach ($oStruct->sizes as $sSize => $oSize) {
			$this->aSizes[$sSize] = array(
				'w' => $oSize->w,
				'h' => $oSize->h,
				'resize' => $oSize->resize,
				'css' => sprintf('width:%spx; height:%spx;', $oSize->w, $oSize->h),
			);
		}
	}

	/**
	 * Returns the media ID
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->sId;
	}

	/**
	 * Returns image-url for specified size
	 * 
	 * @param string $sBase
	 * @param string $sSize
	 * @return string
	 */
	protected function getImageSize($sBase, $sSize)
	{
		if ($sSize == static::SIZE_MEDIUM) {
			return $sBase;
		} else {
			return $sBase . ':' . $sSize;
		}
	}

	/**
	 * Return url to image
	 *
	 * Returns path to medium sized image by default
	 *
	 * @throws InvalidArgumentException On invalid size
	 * 
	 * @param string $sSize One of the SIZE_* constants to set prefered size
	 */
	public function getUrl($sSize = self::SIZE_MEDIUM)
	{
		if (!array_key_exists($sSize, $this->getSizes())) {
			throw new InvalidArgumentException('Invalid size requested');
		}

		return $this->getImageSize($this->sImage, $sSize);
	}

	/**
	 * Return url to image with secure protocol
	 *
	 * Returns path to medium sized image by default
	 *
	 * @throws InvalidArgumentException On invalid size
	 *
	 * @param string $sSize One of the SIZE_* constants to set prefered size
	 */
	public function getUrlSecure($sSize = self::SIZE_MEDIUM)
	{
		if (!array_key_exists($sSize, $this->getSizes())) {
			throw new InvalidArgumentException('Invalid size requested');
		}

		return $this->getImageSize($this->sImageSecure, $sSize);
	}

	/**
	 * Text how it was extracted from the tweet
	 *
	 * Links to Twitter-page with image overlay and tweet text
	 *
	 * @return string
	 */
	public function getText()
	{
		return $this->sText;
	}

	/**
	 * Text how Twitter suggest we display it; usually a shortened url without http-prefix
	 * Links to Twitter-page with image overlay and tweet text
	 *
	 * If Twitter did not provide a display_url, we return the original url
	 */
	public function getTextDisplay()
	{
		return $this->sTextDisplay;
	}

	/**
	 * Returns the various sizes this image is available in
	 *
	 * @return array
	 */
	public function getSizes()
	{
		return $this->aSizes;
	}

	/**
	 * Return array with details for requested size
	 *
	 * @example array(4) {
	 *     ["w"]=> int
	 *     ["h"]=> int
	 *     ["resize"]=> string("fit"|"crop")
	 *     ["css"]=> string("width:123px; height:123px;")
	 *   }
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sSize
	 * @return array
	 */
	public function getSizeDetails($sSize)
	{
		$aSizes = $this->getSizes();
		if (!array_key_exists($sSize, $aSizes)) {
			throw new InvalidArgumentException('Invalid size requested');
		}

		return $aSizes[$sSize];
	}
}
?>