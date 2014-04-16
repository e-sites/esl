<?php
/**
 * Jsend data object
 *
 * Don't have to think about how the response should look. Just create the correct response status by one of the factories and assign the properties you wish you return.
 *
 * Consider this an output-layer, not a data container. Once set, values can not be removed or read.
 * Make up your mind about the content before use
 *
 * @see http://labs.omniti.com/labs/jsend
 * 
 * @package Jsend
 * @version $Id: Jsend.php 665 2014-02-14 14:47:09Z fpruis $
 */
class ESL_Jsend
{
	// Three types of responses. Use the factory methods to create an instance with one of these
	const STATUS_SUCCESS = 'success';
	const STATUS_FAIL    = 'fail';
	const STATUS_ERROR   = 'error';

	/**
	 * The type of response, either "success", "fail" or "error"
	 *
	 * @var string
	 */
	protected $sStatus = null;

	/**
	 * Envelope for all data. Set with the magic setter
	 *
	 * @var array
	 */
	protected $aData = null;

	/**
	 * In case the status is "error", a meaningful, end-user-readable (or at the least log-worthy) message, explaining what went wrong.
	 *
	 * @var string
	 */
	protected $sMessage = null;

	/**
	 * In case the status is "error", a numeric code corresponding to the error, if applicable
	 *
	 * @var int
	 */
	protected $iCode = null;

	/**
	 * Use one of the factory methods (createSuccess, createFail or createError) to create a new instance
	 *
	 * @param string $sStatus
	 */
	protected function __construct($sStatus)
	{
		$this->sStatus = $sStatus;
	}

	/**
	 * Automatically return string notation for Jsend object
	 *
	 * @return string JSON encoded Jsend object
	 */
	public function __toString()
	{
		return $this->getString();
	}

	/**
	 * Set a value
	 *
	 * Each 'name' will be an element inside the data-node with it's value.
	 *
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue)
	{
		if (empty($sName) || !preg_match('/^[a-z_][a-z0-9_]*$/i', $sName)) {
			throw new InvalidArgumentException("A valid element name is required. Use a textual or numeral value.");
		}

		if (is_object($mValue)) {
			$mValue = (array) $mValue;
		}

		$this->aData[$sName] = $mValue;
	}

	/**
	 * All went well, and (usually) some data is returned.
	 *
	 * @return ESL_Jsend
	 */
	static public function createSuccess()
	{
		return new static(static::STATUS_SUCCESS);
	}

	/**
	 * There was a problem with the data submitted, or some pre-condition of the API call wasn't satisfied
	 *
	 * @return ESL_Jsend
	 */
	static public function createFail()
	{
		return new static(static::STATUS_FAIL);
	}

	/**
	 * An error occurred while processing the request, i.e. an exception was thrown
	 *
	 * Use createFail() if it was the users fault something went wrong, like missing or invalid input-data. Use createError() for
	 * example on server-side exceptions
	 *
	 * @param string $sMessage A meaningful, end-user-readable (or at the least log-worthy) message, explaining what went wrong.
	 * @param int $iCode An optional numeric code corresponding to the error, if applicable
	 * @return ESL_Jsend
	 */
	static public function createError($sMessage, $iCode = null)
	{
		if (empty($sMessage) || !is_scalar($sMessage)) {
			throw new InvalidArgumentException('You are required to provide a end-user-readable, or at least log-worthy, textual message.');
		}
		if (null !== $iCode && (!is_int($iCode) || 0 > $iCode)) {
			throw new InvalidArgumentException('Code should be a numeric code corresponding to the error');
		}

		$oJsend = new static(static::STATUS_ERROR);
		$oJsend->sMessage = (string) $sMessage;
		$oJsend->iCode = $iCode;

		return $oJsend;
	}

	/**
	 * Returns object formatted according to Jsend-spec
	 * 
	 * @return stdClass Jsend formatted data
	 */
	public function getObject()
	{
		$oResponse = new stdClass;
		$oResponse->status = $this->sStatus;

		// Errors have a message and optionally a code
		if (static::STATUS_ERROR == $this->sStatus) {
			$oResponse->message = $this->sMessage;
			if (null !== $this->iCode) {
				$oResponse->code = $this->iCode;
			}
		}

		if (null !== $this->aData) {
			$oResponse->data = $this->aData;
		} elseif (static::STATUS_SUCCESS == $this->sStatus || static::STATUS_FAIL == $this->sStatus) {
			// Key 'data' is required when status is 'success' or 'fail'
			$oResponse->data = null;
		}

		return $oResponse;
	}

	/**
	 * Returns the Jsend formatted object encoded in json
	 *
	 * @return string JSON encoded Jsend object
	 */
	public function getString()
	{
		return json_encode($this->getObject());
	}

	/**
	 * Sets multiple values in bulk from an array, for example from a mysql result row
	 *
	 * @param array $aData Associative array with key=>value pairs to be set
	 */
	public function importData(array $aData)
	{
		foreach ($aData as $sKey => $mValue) {
			$this->__set($sKey, $mValue);
		}
	}
}
?>