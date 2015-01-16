<?php
/**
 * Buckaroo configuration
 *
 * Contains information required to work with Buckaroo
 * 
 * @package Buckaroo
 * @version $Id: Config.php 793 2014-09-19 14:43:20Z fpruis $
 */
class ESL_Buckaroo_Config
{
	/**
	 * Can be used with setLocale() to get labels for services in the respective language
	 */
	const LOCALE_NL = 'nl-NL'; // Dutch - The Netherlands
	const LOCALE_EN = 'en-US'; // English - United Kingdom
	const LOCALE_DE = 'de-DE'; // German - Germany
	const LOCALE_FR = 'fr-FR'; // French - France

	const CHANNEL_WEB = 'web';
	const CHANNEL_BACKOFFICE = 'backoffice';

	/**
	 * Merchant ID
	 *
	 * @var string
	 */
	protected $sMerchantKey;

	/**
	 * Secret key
	 * 
	 * @var string
	 */
	protected $sSecretKey;

	/**
	 * Whether to use the test enviroment, or live
	 * 
	 * @var bool
	 */
	protected $bIsInTest;

	/**
	 *
	 * @var string
	 */
	protected $sLocale;

	/**
	 *
	 * @var string
	 */
	protected $sChannel;

	/**
	 * @param string $sMerchantKey Merchant ID
	 * @param string $sSecretKey Secret key
	 * @param bool $bIsInTest
	 */
	public function __construct($sMerchantKey, $sSecretKey, $bIsInTest)
	{
		if (!is_string($sMerchantKey) || $sMerchantKey == '') {
			throw new InvalidArgumentException("Argument #1 (merchant key) should be a string");
		}
		if (!is_string($sSecretKey) || $sSecretKey == '') {
			throw new InvalidArgumentException("Argument #2 (secret key) should be a string");
		}
		if (!is_bool($bIsInTest)) {
			throw new InvalidArgumentException("Argument #3 (in test) should be a bool");
		}
		$this->sMerchantKey = $sMerchantKey;
		$this->sSecretKey = $sSecretKey;
		$this->bIsInTest = $bIsInTest;

		$this->sLocale = static::LOCALE_NL;
		$this->sChannel = static::CHANNEL_WEB;
	}

	/**
	 * Merchant ID
	 * 
	 * @return string
	 */
	public function getMerchantKey()
	{
		return $this->sMerchantKey;
	}

	/**
	 * Secret key
	 *
	 * @return string
	 */
	public function getSecretKey()
	{
		return $this->sSecretKey;
	}

	/**
	 * The gateway to communicate with.
	 *
	 * Depens on whether we are in test of live enviroment
	 * 
	 * @return string
	 */
	public function getGatewayHost()
	{
		return ($this->bIsInTest ? 'testcheckout.buckaroo.nl' : 'checkout.buckaroo.nl');
	}

	/**
	 * Set the locale to use when fetching texts
	 *
	 * Language for payment method labels and date and number formatting
	 *
	 * @param string $sLocale ISO culture code
	 */
	public function setLocale($sLocale)
	{
		if (!in_array($sLocale, array(static::LOCALE_NL, static::LOCALE_EN, static::LOCALE_DE, static::LOCALE_FR))) {
			throw new InvalidArgumentException("Given locale is not supported by Buckaroo. Use one of the LOCALE-constants");
		}
		$this->sLocale = $sLocale;
	}

	/**
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return $this->sLocale;
	}

	/**
	 * @param string $sLocale ISO culture code
	 */
	public function setChannel($sChannel)
	{
		if (!in_array($sChannel, array(static::CHANNEL_WEB, static::CHANNEL_BACKOFFICE))) {
			throw new InvalidArgumentException("Given channel is not supported by Buckaroo. Use one of the CHANNEL-constants");
		}
		$this->sChannel = $sChannel;
	}

	/**
	 *
	 * @return string
	 */
	public function getChannel()
	{
		return $this->sChannel;
	}
}
?>