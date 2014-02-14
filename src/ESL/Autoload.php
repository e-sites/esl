<?php
/**
 * Automatically load classes by resolving the classname into paths on the local filesystem
 *
 * PSR-0 guidelines are used when resolving classnames into filenames, but legacy naming conventions using underscores work as well.
 * 
 * By providing a prefix filter the given folder will only be searched for classes using the same prefix
 *
 * @package Autoload
 * @version $Id: Autoload.php 666 2014-02-14 14:49:14Z fpruis $
 */
class ESL_Autoload
{
	/**
	 * Remember whether we registered the magic __autoload function on the SPL autoload stack, so we won't register it twice
	 * 
	 * @var bool
	 */
	static protected $bRegisteredMagicAutoload = false;

	/**
	 * Root folder to search classes in
	 * 
	 * @var string
	 */
	protected $sBasedir;

	/**
	 * Prefix for (psuedo)namespace a class should match for us to try autoloading it
	 *
	 * @var string
	 */
	protected $sFilterNamespace;

	/**
	 * Extension to append to classname
	 * 
	 * @var string
	 */
	protected $sFileExtension = '.php';

	/**
	 * Create and register a new autoloader.
	 *
	 * If a prefix filter is provided, the basedir will only be searched for classes starting with the same prefix.
	 * When resolving the classname into a file location the prefix is stripped, so usually the prefix filter will partially match the basedir
	 *
	 * @example ESL_Autoload::factory('./classes');
	 * @example ESL_Autoload::factory('/Zend/Http', 'Zend_Http_'); // Loading 'Zend_Http_Client' will include '/Zend/Http/Client.php';
	 * 
	 * @throws InvalidArgumentException When path does not exist
	 * @throws InvalidArgumentException When prefix filter does not end in (psuedo)namespace-seperator
	 *
	 * @param string $sBasedir Path to directory, either relative or absolute
	 * @param string $sPrefixFilter Class prefix, should always end in namespace seperator or underscore
	 * @return ESL_Autoload
	 */
	static public function factory($sBasedir, $sPrefixFilter = null)
	{
		$oAutoload = new static($sBasedir);

		if (null !== $sPrefixFilter) {
			$oAutoload->setPrefixFilter($sPrefixFilter);
		}

		$oAutoload->register();

		return $oAutoload;
	}

	/**
	 * Create new autoloader
	 *
	 * @throws InvalidArgumentException When path does not exist
	 * 
	 * @param string $sPath Path to directory
	 */
	protected function __construct($sPath)
	{
		if (!is_string($sPath)) {
			throw new InvalidArgumentException("Invalid path");
		}

		$sPathAbsolute = realpath($sPath);
		if (!is_dir($sPathAbsolute)) {
			throw new InvalidArgumentException("Invalid path");
		}

		$this->sBasedir = $sPathAbsolute;
	}

	/**
	 *
	 */
	public function __destruct()
	{
		$this->unregister();
	}

	public function __clone()
	{
		$this->register();
	}

	/**
	 * Register ESL_Autoload on SPL Autoload stack
	 *
	 * If the oldschool __autoload is defined we preserve it's usage by registering that too, unless that was already done
	 */
	protected function register()
	{
		if (!spl_autoload_register(array($this, 'load'))) {
			throw new RuntimeException('Failed to register on autoload stack');
		}

		if (!static::$bRegisteredMagicAutoload && function_exists('__autoload') && !in_array('__autoload', spl_autoload_functions())) {
			// Preserve global __autoload function by explicitly prepending it on the SPL autoloader stack
			static::$bRegisteredMagicAutoload = spl_autoload_register('__autoload', true, true);
		}
	}

	/**
	 * Remove from autoload stack.
	 *
	 * Instance becomes useless after unregistering
	 * 
	 * @return bool
	 */
	public function unregister()
	{
		return spl_autoload_unregister(array($this, 'load'));
	}

	/**
	 * Set the file extension that is added to a classname
	 *
	 * Default extension is '.php'
	 * 
	 * @throws InvalidArgumentException On invalid file extension
	 * 
	 * @param string $sExtension
	 * @return ESL_Autoload Returns self
	 */
	public function setFileExtension($sExtension)
	{
		if (!is_string($sExtension) || !preg_match('/\.[a-z]+$/i', $sExtension)) {
			throw new InvalidArgumentException('Invalid file extension');
		}

		$this->sFileExtension = $sExtension;
		return $this;
	}

	/**
	 * If a prefix filter is set, the autoloader will only try to load classes matching this prefix
	 *
	 * @throws InvalidArgumentException When prefix filter does not end in (psuedo)namespace-seperator
	 * 
	 * @param string $sFilter Class prefix, should always end in namespace seperator or underscore
	 * @return ESL_Autoload Returns self
	 */
	public function setPrefixFilter($sFilter)
	{
		if (!is_string($sFilter)) {
			throw new InvalidArgumentException("Invalid prefix filter");
		}
		if (!preg_match('/^([a-z][a-z0-9_]*(\\\|_))+$/i', $sFilter)) {
			throw new InvalidArgumentException("Invalid prefix. Should be part of a valid namespace or classname, and end with a namespace seperator or underscore");
		}

		$this->sFilterNamespace = $sFilter;
		return $this;
	}

	/**
	 * Tries to load the class
	 *
	 * Returns whether loading was successfull or not
	 * 
	 * @param string $sClass
	 * @return bool
	 */
	public function load($sClass)
	{
		if ($this->sFilterNamespace) {
			if (0 !== strpos($sClass, $this->sFilterNamespace)) {
				// Class does not match the prefix filter, skip this autoloader
				return false;
			}

			// Remove prefix from the classname we are looking for
			$sClass = substr($sClass, strlen($this->sFilterNamespace));
		}

		$sFilePath = $this->resolve($sClass);

		// There is no such file
		if (!is_readable($sFilePath)) {
			return false;
		}

		include_once $sFilePath;
		return true;
	}

	/**
	 * Resolve FQN into path
	 * 
	 * @param string Classname
	 * @return string Filepath
	 */
	protected function resolve($sClass)
	{
		$aParts = explode('\\', $sClass);
		// Get the last part (the classname), convert underscores to DIRECTORU_SEPERATOR's and append the file extension, then append it back
		$aParts[] = str_replace('_', DIRECTORY_SEPARATOR, array_pop($aParts)) . $this->sFileExtension;
		// Implode namespaces and filename with a directory seperator
		return $this->sBasedir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $aParts);
	}
}

?>