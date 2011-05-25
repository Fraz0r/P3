<?php

namespace P3;

/**
 * Description of Mail
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Mail 
{
	public static $SMTP = array(
		'host' => null,
		'port' => 25,
		'auth' => true,
		'username' => null,
		'password' => null
	);
}

?>