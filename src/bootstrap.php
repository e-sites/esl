<?php
/**
 * Boot the ESL
 *
 * After booting any ESL-prefixed package can be used without further action.
 *
 * @package ESL
 * @version $Id: bootstrap.php 661 2014-02-14 13:44:44Z fpruis $
 */
require_once realpath(dirname(__FILE__)) . '/ESL.php';
ESL::boot();
?>