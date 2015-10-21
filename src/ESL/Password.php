<?php
/**
 * Simple but secure password hashing
 *
 * Uses Bcrypt to create randomly salted hashes and offers a timing-attack safe way to verify a plain text against a hash.
 *
 * @link https://wiki.php.net/rfc/password_hash
 * @link https://github.com/ircmaxell/password_compat password_compat
 *
 * @example examples/Password/verify.php
 *
 * @package Password
 * @version $Id: Password.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Password
{
	const CRYPT_HASH = '2y';
	const CRYPT_COST = 10;

	/**
	 * The hash
	 * 
	 * @var string
	 */
	protected $sHash = null;

	/**
	 * Algorithm of the hash
	 * 
	 * @var string
	 */
	protected $sAlgorithm = null;

	/**
	 * Cost of the hash
	 * 
	 * @var int
	 */
	protected $iAlgorithmCost = null;

	/**
	 * Hash a password
	 *
	 * The hash currently is 60 characters in length, but might grow longer when we increase security in the future.
	 * Store this hash in a varchar(255) ascii_bin column so you will not run the risk of truncating the hash
	 *
	 * @throws InvalidArgumentException If passed password is not a string
	 * @throws RuntimeException If hashing failed
	 * 
	 * @param string $sPassword The plaintext password that needs hashing
	 * @return string Hashed password
	 */
	static public function hash($sPassword)
	{
		if (!is_string($sPassword)) {
			throw new InvalidArgumentException("Password must be a string");
		}

		// Create a salt to use with Blowfish
		$sSalt = sprintf('$%2s$%02d$%.22s', static::CRYPT_HASH, static::CRYPT_COST, static::generateSalt());
		// Hash the password using the salt
		$sHash = crypt($sPassword, $sSalt);;

		// Test result of crypt()
		if (!$sHash || strlen($sHash) <= 13) {
			throw new RuntimeException("Unabled to create valid hash.");
		}

		return $sHash;
	}

	/**
	 * Generate a random salt
	 *
	 * 22 chars of length (11 bytes in hex)
	 *
	 * @return string [a-z0-9\.]{22}
	 */
	static protected function generateSalt()
	{
		if (function_exists('openssl_random_pseudo_bytes')) {
			$sSalt = bin2hex(openssl_random_pseudo_bytes(11));
		} else {
			$sBuffer = '';
			$iLength = 11;
			while ($iLength--) {
				$sBuffer .= chr(mt_rand(0, 255));
			}
			$sSalt = bin2hex($sBuffer);
		}

		return $sSalt;
	}

	/**
	 * With the instance you are abled to test a hash against a plain-text password, and determine if rehashing is required
	 * 
	 * @throws InvalidArgumentException On invalid hash
	 *
	 * @param string $sHash
	 */
	public function __construct($sHash)
	{
		if (!is_string($sHash)) {
			throw new InvalidArgumentException("Hash must be a string");
		}
		if (!preg_match('/^\$(\w\w)\$(\d\d)\$.+/', $sHash, $aPreg)) {
			throw new InvalidArgumentException('Hash is not in a valid format. Only use ESL_Password with hashes it has created itself.');
		}

		$this->sHash = $sHash;
		$this->sAlgoritm = $aPreg[1];
		$this->iAlgoritmCost = (int) $aPreg[2];
	}

	/**
	 * The hash can get up to 255 characters in length. Make sure your database column is large enough
	 *
	 * @return string
	 */
	public function getHash()
	{
		return $this->sHash;
	}

	/**
	 * Verify a password against the hash using a timing attack resistant approach
	 *
	 * @param string $sPassword
	 * @return bool If the password matches the hash
	 */
	public function verify($sPassword)
	{
		if (!is_string($sPassword)) {
			throw new InvalidArgumentException("Password must be a string");
		}

		$sVerifyHash = crypt($sPassword, $this->sHash);
		if (!$sVerifyHash || strlen($sVerifyHash) <= 13) {
			return false;
		}

		$sTestHash = str_pad($sVerifyHash, strlen($this->sHash), "\0", STR_PAD_RIGHT);
		$sExpectedHash = str_pad($this->sHash, strlen($sVerifyHash), "\0", STR_PAD_RIGHT);

		// Timing-attack safe way to check whether the hashes match
		// Because we used the hash as salt, the plain-text should be hashed into the same hash
		$iDiff = 0;
		for ($i = 0; $i < strlen($sTestHash); $i++) {
			$iDiff |= (ord($sTestHash[$i]) ^ ord($sExpectedHash[$i]));
		}

		return ($iDiff === 0);
	}

	/**
	 * Determine if the password hash needs to be rehashed according to the current security settings
	 *
	 * If the answer is true, after validating the password using verify(), rehash the plain password and store the new hash
	 *
	 * @return boolean True if the password needs to be rehashed.
	 */
	public function needsRehash()
	{
		if ($this->sAlgoritm != static::CRYPT_HASH) {
			return true;
		}
		if ($this->iAlgoritmCost != static::CRYPT_COST) {
			return true;
		}

		return false;
	}
}
?>