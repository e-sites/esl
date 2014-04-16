<?php
/**
 * SimpleXml
 *
 * This class is used as an extension for the functions:
 * - simplexml_load_file
 * - simplexml_load_string
 *
 * It captures errors that occur during parsing and will throw an exception instead of causing a fatal within libxml
 * Also it provides functionality to load an XML from an URL
 *
 * @package SimpleXml
 * @version $Id
 */
class ESL_SimpleXml
{
	/**
	 * Collection of errors that occured
	 * 
	 * @var array
	 */
	static protected $aErrors;

	/**
	 * Parse a file to an SimpleXMLElement.
	 *
	 * @throws RuntimeException when the file does not exist, is not readable
	 *
	 * @param string $sFileName
	 * @return SimpleXMLElement
	 */
	static public function loadFile($sFileName)
	{
		// Make sure the file exists.
		if (!file_exists($sFileName)) {
			throw new RuntimeException(sprintf('%s::%s File not found: %s ', __CLASS__, __FUNCTION__, $sFileName));
		}

		// The file must be readable.
		if (!is_readable($sFileName)) {
			throw new RuntimeException(sprintf('%s::%s File not readable: %s ', __CLASS__, __FUNCTION__, $sFileName));
		}

		// Enable user error handling (disables libxml error handling, which can cause fatals)
		libxml_use_internal_errors(true);

		$oSimpleXMLElement = simplexml_load_file($sFileName);

		static::handleErrors();

		// Return SimpleXMLElement.
		return $oSimpleXMLElement;
	}

	/**
	 * Accepts a string and attempts to create a SimpleXMLElement.
	 *
	 * @param string $sXml
	 * @return SimpleXMLElement
	 */
	static public function loadString($sXml)
	{
		// Enable user error handling (disables libxml error handling, which can cause fatals)
		libxml_use_internal_errors(true);

		$oSimpleXMLElement = simplexml_load_string($sXml);

		static::handleErrors();

		// Return SimpleXMLElement.
		return $oSimpleXMLElement;
	}

	/**
	 * Accepts an URL as resource and attempts to load it in a SimpleXMLElement.
	 *
	 * @throws RuntimeException when the url is not available.
	 * @throws InvalidArgumentException when given location is not a valid URL
	 * 
	 * @param string $sXmlLocation
	 * @return SimpleXMLElement
	 */
	static public function loadUrl($sXmlLocation)
	{
		$aUrlParts = parse_url($sXmlLocation);

		if (!is_array($aUrlParts)) {
			throw new InvalidArgumentException('Invalid resource, ' . $sXmlLocation . ' is not a valid URL');
		}

		$mHandler = curl_init($sXmlLocation);

		if (!is_resource($mHandler)) {
			throw new RuntimeException('Unable to load XML from ' . $sXmlLocation);
		}

		curl_setopt($mHandler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($mHandler, CURLOPT_HEADER, false);

		$sResponse = curl_exec($mHandler);
		curl_close($mHandler);

		if ($sResponse === false) {
			throw new RuntimeException('Unable to load XML from ' . $sXmlLocation);
		}

		// Use the content to create a simplexmlelement.
		return static::loadString($sResponse);
	}

	/**
	 * Internal method to handle libXML errors in case an invalid XML is parsed. It will also
	 * set the errorhandling back to default (libxml error handling). Optional found erros can
	 * be retrieved with getErrors method
	 *
	 * @throws RuntimeException When invalid XML is parsed
	 */
	static protected function handleErrors()
	{
		static::$aErrors = libxml_get_errors();

		// Disable user error handling (enables libxml error handling, back to default)
		libxml_use_internal_errors(false);

		if (!empty(static::$aErrors)) {
			// Enable use internal errors.
			throw new RuntimeException(sprintf('%s::%s Invalid XML string', __CLASS__, __FUNCTION__));
		}
	}

	/**
	 * Returns array of found XML errors during parsing
	 * 
	 * @return array
	 */
	static public function getErrors()
	{
		return static::$aErrors;
	}
}
?>