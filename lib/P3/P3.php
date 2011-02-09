<?php

/**
 * Description of P3
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
final class P3
{
	private static $_env = null;

	public static function getEnv()
	{
		if(is_null(self::$_env)) self::$_env = self::_determineEnv();

		return self::$_env;
	}

	public static function development()
	{
		return self::$_env == 'development' || self::$_env == 'dev';
	}

	public static function production()
	{
		return self::$_env == 'production' || self::$_env == 'prod';
	}

	private static function _determineEnv()
	{
		$env = getenv('P3_Env');
		return ($env) ? $env : 'development';
	}
}

/*  Run after included  */
P3::getEnv();

?>