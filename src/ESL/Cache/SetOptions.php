<?php
/**
 * Options available when setting keys in cache
 *
 * @package Cache
 * @version $Id: SetOptions.php 664 2014-02-14 14:36:52Z fpruis $
 */
class ESL_Cache_SetOptions
{
	/**
	 * TTL presets
	 */
	const TTL_FOREVER = 0;
	const TTL_MINUTE = 60;
	const TTL_HOUR = 3600;
	const TTL_DAY = 86400;
	const TTL_WEEK = 604800;

	/**
	 * Offset in seconds from now
	 *
	 * @internal Memcache can only use a maximum offset of 60x60x24x30. Beyond that a timestamp is assumed. Because we always add the current timestamp to the given offset
	 * this limit does not apply. You are free to use any offset.
	 *
	 * @var int
	 */
	protected $iTtl = null;

	/**
	 * Tags set with this key
	 * 
	 * @var array
	 */
	protected $aTags = array();

	/**
	 * Loader for ESL_Cache_SetOptions
	 *
	 * Enables chaining
	 *
	 * @return ESL_Cache_SetOptions self
	 */
	static public function create()
	{
		return new static;
	}

	/**
	 * Set the TTL for the key in seconds from now.
	 *
	 * Use one of the ESL_Cache_SetOptions::TTL_* constants or specify your own offset in seconds
	 *
	 * @throws InvalidArgumentException On invalid TTL value
	 *
	 * @param int $iTtl Seconds from now
	 * @return ESL_Cache_SetOptions self
	 */
	public function setTtl($iTtl)
	{
		if (!is_numeric($iTtl) || $iTtl < 0) {
			throw new InvalidArgumentException('Invalid TTL given. Expecting numeric value larger or equal to 0');
		}

		$this->iTtl = (int) $iTtl;
		return $this;
	}

	/**
	 * Returns the TTL
	 * 
	 * @return int TTL in seconds, or null if no ttl was set
	 */
	public function getTtl()
	{
		return $this->iTtl;
	}

	/**
	 * Add a tag
	 *
	 * @throws InvalidArgumentException On duplicate tag
	 * 
	 * @param string $sTag
	 * @return ESL_Cache_SetOptions self
	 */
	public function addTag($sTag)
	{
		if (false !== in_array($sTag, $this->aTags)) {
			throw new InvalidArgumentException("Tag '$sTag' is already set.");
		}

		$this->aTags[] = $sTag;

		return $this;
	}

	/**
	 * Add multiple tags at once
	 * 
	 * @throws InvalidArgumentException On duplicate tag
	 *
	 * @param array $aTags List of tags
	 * @return ESL_Cache_SetOptions self
	 */
	public function addTags(array $aTags)
	{
		foreach ($aTags as $sTag) {
			$this->addTag($sTag);
		}

		return $this;
	}

	/**
	 * Return tags
	 * 
	 * @return array Tags
	 */
	public function getTags()
	{
		return $this->aTags;
	}
}
?>