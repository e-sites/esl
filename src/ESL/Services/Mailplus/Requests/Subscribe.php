<?php
/**
 * Request object to subscribe a new contact.
 *
 * Build your instance and use it with ESL_Services_Mailplus::subscribe()
 *
 * @package Mailplus
 * @version $Id: Subscribe.php 702 2014-05-12 12:11:17Z fpruis $
 */
class ESL_Services_Mailplus_Requests_Subscribe implements ESL_Services_Mailplus_Requests_Interface
{
	const CHANNEL_EMAIL = 'EMAIL';

	protected $aChannels = array();
	protected $aKeyValuePairs = array();

	/**
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sEmail Sets the e-mailaddress for the user to subscribe.
	 */
	public function __construct($sEmail)
	{
		$this->addKeyValuePair('email', $sEmail);
		$this->addChannel(self::CHANNEL_EMAIL);
	}

	/**
	 * Adds the specified channel $sChannelName to the channels via which the user allows us to be reached.
	 *
	 * Either EMAIL, SMS or DIRECTMAIL
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sChannelName
	 */
	public function addChannel($sChannelName)
	{
		if (empty($sChannelName) || !is_string($sChannelName) || !constant('self::CHANNEL_' . $sChannelName)) {
			throw new InvalidArgumentException('$sChannelName is empty, not a string or an unknown channel');
		}

		$this->aChannels[] = $sChannelName;
	}

	/**
	 * Adds a profileField with the request from which specific selections can be
	 * made within MailPlus, e.g. 'profileField1'.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $sProfileField
	 * @param string $sValue
	 */
	public function addProfileField($sProfileField, $sValue)
	{
		if (empty($sProfileField) || !is_string($sProfileField) || strpos($sProfileField, 'profileField') !== 0) {
			throw new InvalidArgumentException('$sProfileField is empty, not a string or does not start with profileField');
		}
		if (empty($sValue) || !is_string($sValue)) {
			throw new InvalidArgumentException('$sValue is empty or not a string');
		}

		$this->addKeyValuePair($sProfileField, $sValue);
	}

	/**
	 * Adds a freeField with the request from which specific selections can be
	 * made within MailPlus, e.g. 'freeField1'.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $sFreeField
	 * @param string $sValue
	 */
	public function addFreeField($sFreeField, $sValue)
	{
		if (empty($sFreeField) || !is_string($sFreeField) || strpos($sFreeField, 'freeField') !== 0) {
			throw new InvalidArgumentException('$sProfileField is empty, not a string or does not start with profileField');
		}
		if (empty($sValue) || !is_string($sValue)) {
			throw new InvalidArgumentException('$sValue is empty or not a string');
		}

		$this->addKeyValuePair($sFreeField, $sValue);
	}

	/**
	 * Sets whether to add the user to the test group or not.
	 * 
	 * @param bool $bTestgroup
	 * @throws InvalidArgumentException
	 */
	public function setInTestGroup($bTestgroup = true)
	{
		if (!is_bool($bTestgroup)) {
			throw new InvalidArgumentException('$bYesNo is not a bool');
		}

		if ($bTestgroup === true) {
			$this->addKeyValuePair('testGroup', 'Ja');
		}
	}

	/**
	 * Create an array with parameters for use in the webservice request
	 * 
	 * @return array
	 */
	public function toArray()
	{
		$aArray = array(
			'keys' => array_keys($this->aKeyValuePairs),
			'values' => array_values($this->aKeyValuePairs),
			'visible' => true
		);

		if (count($this->aChannels) > 0) {
			$aArray['channels'] = implode(',', $this->aChannels);
		}

		return $aArray;
	}

	/**
	 *
	 * @param string $sName
	 */
	public function setFirstname($sName)
	{
		$this->addKeyValuePair('firstName', $sName);
	}

	/**
	 *
	 * @param string $sName
	 */
	public function setLastname($sName)
	{
		$this->addKeyValuePair('lastName', $sName);
	}

	/**
	 *
	 * @throws InvalidArgumentException
	 * 
	 * @param string Either 'M' for male or 'F' for female.
	 */
	public function setGender($sGender)
	{
		if (!in_array($sGender, array('M', 'F'))) {
			throw new InvalidArgumentException("Gender is required to be either 'M' or 'F'");
		}
		$this->addKeyValuePair('gender', $sGender);
	}

	/**
	 * Adds the specified $sKey and $sValue as key-value pair to the list of
	 * key-value pairs -properties of the user.
	 * 
	 * @throws InvalidArgumentException
	 * 
	 * @param string $sKey
	 * @param string $sValue
	 */
	protected function addKeyValuePair($sKey, $sValue)
	{
		if (empty($sKey) || !is_string($sKey)) {
			throw new InvalidArgumentException('$sKey is empty or not a string');
		}
		if (empty($sValue) || !is_string($sValue)) {
			throw new InvalidArgumentException('$sValue is empty or not a string');
		}

		$this->aKeyValuePairs[$sKey] = $sValue;
	}
}
?>