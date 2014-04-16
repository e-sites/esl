<?php
/**
 * ESL
 *
 * Registers our own ESL_Autoload to load any other ESL-package
 * 
 * @version $Id: ESL.php 666 2014-02-14 14:49:14Z fpruis $
 */
class ESL
{
	/**
	 * Singleton instance.
	 * 
	 * @var ESL
	 */
	static protected $oInstance;

	/**
	 * Path to the ESL root.
	 *
	 * @var string
	 */
	protected $sPath = null;

	/**
	 * Returns this ESL instance
	 *
	 * @return ESL
	 */
	static protected function getInstance()
	{
		if (null === self::$oInstance) {
			static::boot();
		}
		return self::$oInstance;
	}

	/**
	 * Boot the ESL. This will register the autoloader and allow all packages to be used without prior manual inclusion
	 *
	 * @return null
	 */
	static public function boot()
	{
		if (null !== self::$oInstance) {
			trigger_error('ESL is already booted', E_USER_NOTICE);
			return;
		}

		self::$oInstance = new static;
	}

	/**
	 * Returns the physical path to the ESL.
	 *
	 * @return string
	 */
	static public function getPath()
	{
		$oEsl = self::getInstance();
		return $oEsl->sPath;
	}

	/**
	 * Create the ESL state
	 */
	protected function __construct()
	{
		// The absolute path to this ESL
		$this->sPath = realpath(__DIR__);

		// Use our own autoloader-package to autoload the other ESL packages
		require_once $this->sPath . '/ESL/Autoload.php';
		ESL_Autoload::factory($this->sPath . '/ESL/', 'ESL_');
	}

	/**
	 * Singleton
	 */
	private final function __clone()
	{
	}
}
?>