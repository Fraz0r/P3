<?php

namespace P3\Cache\Engine;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base 
{
	private static $_cache_handler;
	private static $_cache_handler_class;
	private static $_enabled = false;

	public static function disable()
	{
		self::$_enabled = false;
	}

	public static function enable()
	{
		self::$_enabled = true;
	}

	public static function get($key, $default = null)
	{
		return self::handler()->get($key, $default);
	}

	public static function handler()
	{
		if(!isset(self::$_cache_handler)) {
			$handler_class = self::_get_handler_class();

			self::$_cache_handler = new $handler_class();
		}

		return self::$_cache_handler;
	}

	public static function set_handler($handler)
	{
		self::$_cache_handler_class = $handler;
	}


//- Private Static
	private static function _get_handler_class()
	{
		switch(self::$_cache_handler_class) {
			case Cache\HANDER_APC:
				return '\P3\Cache\Handler\APC';
			default:
				return '\P3\Cache\Handler\None';
		}
	}
}

?>
