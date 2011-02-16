<?php

/**
 * Description of P3
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
final class P3
{
	public static $VERSION = '0.9.3';

	private static $_env = null;

	/**
	 * Returns current development environment
	 * @return string Development Environment
	 */
	public static function getEnv()
	{
		if(is_null(self::$_env)) self::$_env = self::_determineEnv();

		return self::$_env;
	}

	/**
	 * Checks whether or not in development
	 * @return boolean True if in development, false otherwise
	 */
	public static function development()
	{
		return self::$_env == 'development' || self::$_env == 'dev';
	}

	/**
	 * Checks whether or not in production
	 * @return boolean True if in production, false otherwise
	 */
	public static function production()
	{
		return self::$_env == 'production' || self::$_env == 'prod';
	}

	/**
	 * Determines Development Environment
	 * @return string Development Environment
	 */
	private static function _determineEnv()
	{
		$env = getenv('P3_Env');
		return ($env) ? $env : 'development';
	}
}

/*  Run after included  */
P3::getEnv();

?>
