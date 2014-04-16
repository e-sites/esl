<?php
/**
 * Persistent cache
 *
 * High-performance, memory object caching system, generic in nature, but intended for use in speeding up dynamic web applications by alleviating database load.
 *
 * @package Cache
 * @version $Id: Cache.php 664 2014-02-14 14:36:52Z fpruis $
 */
class ESL_Cache
{
	/**
	 * Prefix appended to all keys set with this class. Prevents collisions with custom Memcached usage
	 * 
	 * @var string
	 */
	const PREFIX = 'ESL';

	/**
	 * Resultcode if action completed succesfully
	 */
	const RESULT_OK = 0;

	/**
	 * Resultcode if action failed, for whatever reason.
	 *
	 * More detailed statusses might be added in the future. Therefor you should check for !RESULT_OK, instead of hard checking for this one.
	 */
	const RESULT_FAIL = 1;

	/**
	 * Naming rule for namespaces, keys and tags. PCRE syntax
	 */
	const ENTITY_FORMAT = '[a-z](?:_?[a-z0-9])*';

	/**
	 * We use Memcached to actually store all data. But the outside world shouldn't know.
	 * @internal Might become APC-cache some day.
	 *
	 * @var Memcached
	 */
	private $oMemcached;

	/**
	 * The namespace unique for the project this class is used in, to prevent colissions with other projects
	 * 
	 * @var string
	 */
	protected $sNamespace;

	/**
	 * The prefix as used, build with the class prefix and the given namespace
	 * 
	 * @var string
	 */
	protected $sKeyPrefix;

	/**
	 * Default TTL to use when none is specified while calling set/add/replace
	 * 
	 * @var int 
	 */
	protected $iDefaultTtl = 3600;

	/**
	 * Local storage for tag versions to prevent lots of lookups
	 *
	 * @var array
	 */
	protected $aTags = array();

	/**
	 * Returns whether the given name can be used as entitiy (namespace, key, tag) name
	 * 
	 * @param string $sName
	 * @return bool
	 */
	static public function isValidEntity($sName)
	{
		try {
			static::assertEntity($sName);
		} catch (InvalidArgumentException $e) {
			return false;
		}

		return true;
	}

	/**
	 * Asserts an entity name, like keys and tags, only contains allowed characters
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $sName
	 */
	static protected function assertEntity($sName)
	{
		if (!is_string($sName)) {
			throw new InvalidArgumentException('Entities should be strings, ' . gettype($sName) .  ' given: ' . var_export($sName, true));
		}
		if (!preg_match('/^' . static::ENTITY_FORMAT . '$/i', $sName)) {
			throw new InvalidArgumentException("Invalid entity name. '$sName' does not conform to '" . static::ENTITY_FORMAT . "'.");
		}
	}

	/**
	 * Create a new instance. Make sure to supply a unique namespace for the project you use this class in.
	 *
	 * Namespaces may, just like keys and tags, only contain letters, digits and underscores. Act like they are MySQL table names.
	 * 
	 * @throws InvalidArgumentException On invalid namespace naming
	 * @throws RuntimeException If cache is unavailable
	 *
	 * @param Memcached $oMemcached Memcached instance
	 * @param string $sNamespace Namespace wherein all keys and tags are stored. Use for example the project name
	 */
	public function __construct(Memcached $oMemcached, $sNamespace)
	{
		static::assertEntity($sNamespace);

		$this->oMemcached = $oMemcached;

		// The prefix to use with all keys
		$this->sNamespace = $sNamespace;

		$this->buildPrefix($this->getVersionForNamespace());
	}

	/**
	 * Create a new instance for localhost
	 *
	 * Make sure to supply a unique namespace for the project you use this class in.
	 *
	 * @throws InvalidArgumentException On invalid namespace naming
	 * @throws RuntimeException If cache is unavailable
	 *
	 * @param string $sNamespace Namespace wherein all keys and tags are stored. Use for example the project name
	 * @return ESL_Cache
	 */
	static public function factory($sNamespace)
	{
		try {
			$oMemcached = new Memcached();
			// Note; After servers are added/changed key distribution might change, causing cache misses
			$oMemcached->addServers(
				array(
					array('localhost', 11211),
				)
			);
		} catch (Exception $e) {
			throw new RuntimeException('Unabled to construct Memcached object', 0, $e);
		}

		return new static($oMemcached, $sNamespace);
	}

	/**
	 * Generate the string prepended to all the keys allowing us to flush an entire namespace, without touching anything else
	 *
	 * @param int $iNamespaceVersion
	 */
	protected function buildPrefix($iNamespaceVersion)
	{
		// Build key used as prefix for all data.
		$this->sKeyPrefix = static::PREFIX . ';' . $this->sNamespace . '=' . $iNamespaceVersion;
	}

	/**
	 * Return the current version for the given namespace
	 *
	 * This value will change after flushing so new keys are created
	 * 
	 * @return int
	 */
	protected function getVersionForNamespace()
	{
		$sKeyNamespace = $this->buildKeyForNamespace();
		if (false === ($iNamespaceVersion = $this->oMemcached->get($sKeyNamespace))) {
			// Create namespace version
			$iNamespaceVersion = 1;
			$this->oMemcached->set($sKeyNamespace, $iNamespaceVersion);
		}

		return $iNamespaceVersion;
	}

	/**
	 * Return the current version for the given tag
	 *
	 * This value will change after flushing so new keys are created
	 *
	 * @param string $sTag
	 * @return int
	 */
	protected function getVersionForTag($sTag)
	{
		if (isset($this->aTags[$sTag])) {
			$iTagVersion = $this->aTags[$sTag];
		} else {
			$sKeyTag = $this->buildKeyForTag($sTag);
			// Get and remember value for unknown tag
			if (false === ($iTagVersion = $this->oMemcached->get($sKeyTag))) {
				$iTagVersion = 1;
				$this->oMemcached->set($sKeyTag, $iTagVersion);
			}
			$this->aTags[$sTag] = $iTagVersion;
		}

		return $iTagVersion;
	}

	/**
	 * Return the key name where we store the namespace version
	 *
	 * This key is in the form [prefix];n:[namespace]
	 * 
	 * @return string
	 */
	protected function buildKeyForNamespace()
	{
		return static::PREFIX . ';n:' . $this->sNamespace;
	}

	/**
	 * Return the key name where we store the tag version
	 *
	 * This key is in the form [prefix];[namespace];t:[tag]
	 * 
	 * @param string $sTag
	 * @return string
	 */
	protected function buildKeyForTag($sTag)
	{
		return $this->sKeyPrefix . ';t:' . $sTag;
	}

	/**
	 * Return the name of the key where we store the tags associated with a key
	 *
	 * This key is in the form [prefix];[namespace];l:[key]
	 *
	 * @param string $sKey
	 * @return string
	 */
	protected function buildKeyForTaglist($sKey)
	{
		return $this->sKeyPrefix . ';l:' . $sKey;
	}

	/**
	 * Return the name of the key where we store the actual data
	 *
	 * This key is in the form [prefix];[namespace];v:[key];[tags]
	 *
	 * @param string $sKey
	 * @return string
	 */
	protected function buildKeyForData($sKey)
	{
		if (null === ($aTags = $this->getTaglist($sKey))) {
			// If the tags dont exist, neither will the value
			return null;
		}

		$sTags= '';
		foreach ($aTags as $sTag) {
			$sTags .= ";$sTag=" . $this->getVersionForTag($sTag);
		}

		return $this->sKeyPrefix . ';v:' . $sKey . ';' . md5($sTags);
	}

	/**
	 * Store an item
	 *
	 * ESL_Cache::set() stores the value under the specified key. The ESL_Cache_SetOptions-parameter can be used to add tags to the key and control TTL.
	 *
	 * The value can be any valid PHP type that is serializable. Resources for example can not be stored.
	 * Tags can be used to be abled to flush all keys with that tag at once. If the functionality for flushing keys is not used, setting tags has no use.
	 * 
	 * @throws InvalidArgumentException On invalid key name
	 * @throws InvalidArgumentException When passing a resource as value
	 *
	 * @param string $sKey
	 * @param mixed $mValue
	 * @param ESL_Cache_SetOptions $oSetOptions
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function set($sKey, $mValue, ESL_Cache_SetOptions $oSetOptions = null)
	{
		static::assertEntity($sKey);

		if (is_resource($mValue)) {
			throw new InvalidArgumentException("The value can not be a resource. Resources can not be serialized.");
		}

		// Gather TTL and tags
		if ($oSetOptions) {
			if (null === ($iTtl = $oSetOptions->getTtl())) {
				// No TTL was set; use default
				$iTtl = $this->iDefaultTtl;
			}

			// Get tags
			$aTags = $oSetOptions->getTags();

			// Validate tags
			foreach ($aTags as $sTag) {
				static::assertEntity($sTag);
			}

			// Force tags to always be in the same order
			sort($aTags);
		} else {
			// Use default TTL
			$iTtl = $this->iDefaultTtl;

			// No tags
			$aTags = array();
		}

		if ($iTtl > 0) {
			// Define TTL in timestamp instead of offset
			$iTtl += time();
		}

		// Store the tags
		if (!$this->oMemcached->set($this->buildKeyForTaglist($sKey), $aTags, $iTtl)) {
			return false;
		}

		// Store the value
		$sKeyData = $this->buildKeyForData($sKey);
		if (null === $sKeyData) {
			return false;
		}

		if (!$this->oMemcached->set($sKeyData, $mValue, $iTtl)) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the tags stored for a key or null if nothing is
	 *
	 * If the taglist key does not exist neither will the data key. Even if a key has no keys associated to it the taglist will exist, though be it empty
	 * 
	 * @param string $sKey
	 * @return array
	 */
	protected function getTaglist($sKey)
	{
		$aTags = $this->oMemcached->get($this->buildKeyForTaglist($sKey));
		if (false === $aTags && $this->oMemcached->getResultCode() != Memcached::RES_SUCCESS) {
			return null;
		}

		return $aTags;
	}

	/**
	 * Retrieve an item
	 *
	 * ESL_Cache::get() returns the item that was previously stored under the key.
	 *
	 * If the key does not exist NULL is returned. If you want to known whether the key was not found or the value is actually NULL,
	 * check whether ESL_Cache::getResultCode() === ESL_Cache::RESULT_OK
	 *
	 * @throws InvalidArgumentException On invalid key name
	 * 
	 * @param string $sKey
	 * @return mixed Returns the value stored in the cache or NULL otherwise.
	 */
	public function get($sKey)
	{
		static::assertEntity($sKey);

		$sKeyData = $this->buildKeyForData($sKey);
		if (null === $sKeyData) {
			return null;
		}

		$mValue = $this->oMemcached->get($sKeyData);
		if (false === $mValue && $this->oMemcached->getResultCode() != Memcached::RES_SUCCESS) {
			// Value isn't set
			return null;
		}

		return $mValue;
	}

	/**
	 * Delete an item
	 *
	 * ESL_Cache::delete() deletes the key from the server.
	 *
	 * @throws InvalidArgumentException On invalid key name
	 *
	 * @param string $sKey
	 * @return bool
	 */
	public function delete($sKey)
	{
		static::assertEntity($sKey);

		if (!$this->oMemcached->delete($this->buildKeyForData($sKey))) {
			// Delete data
			return false;
		}

		$this->oMemcached->delete($this->buildKeyForTaglist($sKey));

		return true;
	}

	/**
	 * Invalidate all items in the namespace
	 *
	 * ESL_Cache::flush() invalidates all existing cache items in the namespace immediately. After invalidation none of the items will be returned in response to a retrieval
	 * command (unless it's stored again under the same key after ESL_Cache::flush() has invalidated the items).
	 *
	 * The flush does not actually free all the memory taken up by the existing items; that will happen gradually as new items are stored.
	 *
	 * @return bool Returns TRUE
	 */
	public function flush()
	{
		$sKeyNamespace = $this->buildKeyForNamespace();
		if (false === ($iNamespaceVersion = $this->oMemcached->increment($sKeyNamespace))) {
			// Create version
			$iNamespaceVersion = 2;
			$this->oMemcached->set($sKeyNamespace, $iNamespaceVersion);
		}

		// Create new prefix with updated namespace version
		$this->buildPrefix($iNamespaceVersion);

		return true;
	}

	/**
	 * Invalidate all items with a specific tag
	 *
	 * @param string $sTag
	 * @return bool Returns TRUE
	 */
	public function flushTag($sTag)
	{
		static::assertEntity($sTag);

		$sKeyTag = $this->buildKeyForTag($sTag);
		if (false === ($iVersion = $this->oMemcached->increment($sKeyTag))) {
			$iVersion = 2;
			// Create version
			$this->oMemcached->set($sKeyTag, $iVersion);
		}

		$this->aTags[$sTag] = $iVersion;
		return true;
	}

	/**
	 * Set the default TTL. Pass 0 (zero) for 'forever'
	 *
	 * @param int $iTtl Offset in seconds from now, or 0 for forever
	 */
	public function setDefaultTtl($iTtl)
	{
		if (!is_numeric($iTtl) || $iTtl < 0) {
			throw new InvalidArgumentException('Invalid TTL given. Expecting numeric value larger or equal to 0');
		}

		$this->iDefaultTtl = (int) $iTtl;
	}

	/**
	 * Return the result code of the last operation
	 *
	 * Returns one of the ESL_Cache::RESULT_* constants that is the result of the last executed method. Never compare the return value directly with RESULT_FAIL to see whether
	 * the action was a success or not. Instead check for != RESULT_OK, because more errorcodes could be added in the future.
	 *
	 * @return int
	 */
	public function getResultCode()
	{
		switch ($this->oMemcached->getResultCode()) {
			case Memcached::RES_SUCCESS:
				return static::RESULT_OK;
				break;
			default:
				return static::RESULT_FAIL;
				break;
		}
	}
}
?>