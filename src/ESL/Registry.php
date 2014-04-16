<?php
/**
 * Static storgage for objects
 *
 * @example ESL_Registry::set('Database', $oDb);
 * @example ESL_Registry::learn('Database', function() {return new Mysql()});
 * @example ESL_Registry::teach('Database', create_function('', 'return new Barbapapa();'));
 *
 * @package Registry
 * @version $Id: Registry.php 663 2014-02-14 14:33:40Z fpruis $
 */
class ESL_Registry
{
	/**
	 * List of all registered objects
	 *
	 * @var array
	 */
	static protected $aInstances = array();

	/**
	 * List of registered functions which will create objects on the first call to get()
	 *
	 * @var array
	 */
	static protected $aBlueprints = array();

	/**
	 * Make sure the given label can be used
	 *
	 * @throws InvalidArgumentException If the given value can not be used as label
	 *
	 * @param string $sLabel
	 */
	static protected function assertLabel($sLabel)
	{
		if (!self::isLabel($sLabel)) {
			throw new InvalidArgumentException("The given value (" . gettype($sLabel) . ") $sLabel can not be used as label.");
		}
	}

	/**
	 * Internal method for actually setting the objects
	 *
	 * @throws InvalidArgumentException If instance is not an object
	 *
	 * @param string $sLabel
	 * @param object $oInstance
	 */
	static protected function setInstance($sLabel, $oInstance)
	{
		if (!is_object($oInstance)) {
			throw new InvalidArgumentException("The given object (" . gettype($oInstance) . ") $oInstance is not an object.");
		}

		self::$aInstances[$sLabel] = $oInstance;
	}

	/**
	 * Store an object in the registry for later retreival with get()
	 *
	 * @throws InvalidArgumentException If label can not be used as label
	 * @throws RuntimeException If label is already used
	 * @throws InvalidArgumentException If instance is not an object
	 * 
	 * @param string $sLabel
	 * @param object $oInstance
	 */
	static public function set($sLabel, $oInstance)
	{
		self::assertLabel($sLabel);
		if (self::exists($sLabel)) {
			throw new RuntimeException("Label '$sLabel' already exists.");
		}

		self::setInstance($sLabel, $oInstance);
	}

	/**
	 * Store a factory which will create an object in the registry.
	 *
	 * The object will not be created untill the first call to get()
	 *
	 * @example
	 *	ESL_Registry::teach('Database', array('Barbapapa', 'getInstance'));
	 *
	 * @throws InvalidArgumentException If label can not be used as label
	 * @throws RuntimeException If label is already used
	 * @throws InvalidArgumentException If callback is not a valid callback
	 * 
	 * @param string $sLabel
	 * @param callback $cFunction Name of function to call. A string for function or array for object and method
	 */
	static public function teach($sLabel, $cFunction)
	{
		self::assertLabel($sLabel);
		if (self::exists($sLabel)) {
			throw new RuntimeException("Label '$sLabel' already exists.");
		}
		if (!is_callable($cFunction)) {
			throw new InvalidArgumentException("The given callback is not callable.");
		}

		self::$aBlueprints[$sLabel] = $cFunction;
	}

	/**
	 * Returns whether the variable can be used as a label
	 *
	 * @param string $sLabel
	 * @return bool
	 */
	static public function isLabel($sLabel)
	{
		return (is_string($sLabel));
	}

	/**
	 * Returns whether the label exists
	 *
	 * @throws InvalidArgumentException If label can not be used as label
	 * 
	 * @param string $sLabel
	 * @return bool
	 */
	static public function exists($sLabel)
	{
		self::assertLabel($sLabel);

		return (array_key_exists($sLabel, self::$aInstances) || array_key_exists($sLabel, self::$aBlueprints));
	}

	/**
	 * Retreive a previously set object by it's label
	 *
	 * @throws InvalidArgumentException If label can not be used as label
	 * @throws RuntimeException If label does not exist
	 * 
	 * @param string $sLabel
	 * @return object
	 */
	static public function get($sLabel)
	{
		self::assertLabel($sLabel);

		if (!array_key_exists($sLabel, self::$aInstances)) {
			if (!array_key_exists($sLabel, self::$aBlueprints)) {
				throw new RuntimeException("Label '$sLabel' does not exist.");
			}
			// Run the factory to set the object
			self::setInstance($sLabel, call_user_func(self::$aBlueprints[$sLabel]));
			unset(self::$aBlueprints[$sLabel]);
		}

		return self::$aInstances[$sLabel];
	}

	/**
	 * Prints a dump of the current registry and its content
	 */
	static public function dump()
	{
		$aDump = array(
			'instances' => array(),
			'blueprints' => array()
		);

		foreach (self::$aInstances as $sLabel => $oInstance) {
			$aDump['instances'][$sLabel] = get_class($oInstance);
		}
		foreach (self::$aBlueprints as $sLabel => $mFunction) {
			$aDump['blueprints'][$sLabel] = $mFunction;
		}

		return $aDump;
	}
}
?>