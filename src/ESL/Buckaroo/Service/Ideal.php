<?php
/**
 * iDeal service
 *
 * With shortcut to get and set Issuer
 *
 * @package Buckaroo
 * @version $Id: Ideal.php 661 2014-02-14 13:44:44Z fpruis $
 */
class ESL_Buckaroo_Service_Ideal extends ESL_Buckaroo_Service
{
	/**
	 *
	 * @return array
	 */
	public function getIssuers()
	{
		return $this->getField('issuer')->getAllowedValues();
	}

	/**
	 * @throws InvalidArgumentException
	 *
	 * @param string $sIssuer
	 */
	public function setIssuer($sIssuer)
	{
		$this->getField('issuer')->setValue($sIssuer);
	}
}

?>