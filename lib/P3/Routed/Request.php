<?php

namespace P3\Routed;

/**
 * Description of Request
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Request 
{
	private static $_instance = null;

	public function __construct()
	{
	}

	public static function singleton()
	{
		if(is_null(self::$_instance))
			self::$_instance = new self;

		return self::$_instance;
	}
}

?>