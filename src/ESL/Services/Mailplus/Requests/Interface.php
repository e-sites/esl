<?php
/**
 * Interface for all requests used with ESL_Services_Mailplus
 *
 * @package Mailplus
 * @version $Id: Interface.php 662 2014-02-14 14:17:32Z fpruis $
 */
interface ESL_Services_Mailplus_Requests_Interface
{
	/**
	 * Return request parameters
	 *
	 * @return array
	 */
	public function toArray();
}
?>