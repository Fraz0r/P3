<?php

namespace P3;


/**
 * Description of initializer
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
final class Initializer 
{
	private static $_env;

	public static function development()
	{
		return !self::development();
	}

	public static function env($env = null)
	{
		if(is_null($env)) {
			if(is_null(self::$_env))
				if(FALSE !== ($env = getenv('P3_Env')))
					self::$_env = $env;
				else
					self::$_env = 'production';

			return self::$_env;
		} else {
			self::$_env = $env;
		}
	}

	public static function globbed_include($globbing_pattern)
	{
		if(count(($files = glob($globbing_pattern))))
			foreach($files as $file)
				include($file);
	}

	public static function globbed_include_phake($globbing_pattern)
	{
		if(count(($files = glob($globbing_pattern))))
			foreach($files as $file)
				\phake\load_runfile($file);
	}

	public static function globbed_require($globbing_pattern)
	{
		if(count(($files = glob($globbing_pattern))))
			foreach($files as $file)
				require($file);
	}

	public static function init()
	{
		self::_register_error_handler();
		self::_set_include_path();
		self::_register_autoloader();

		require(PATH.'/config/defaults.php');
	}

	public static function init_phake()
	{
		self::init();
		self::globbed_include_phake(ROOT.'/lib/tasks/*.phake');
		self::globbed_include_phake(PATH.'/lib/tasks/*.phake');
	}

	public static function run($closure)
	{
		self::init();
		$config = Config\Handler::singleton();
		$closure($config);

		if(file_exists(($env_config = ROOT.'/config/environments/'.self::env().'.php')))
			require($env_config);
	}


	public static function production()
	{
		return self::env() == 'production';
	}

//- Private Static
	private static function _register_autoloader()
	{
		require_once(PATH.'/auto_loader.php');
		spl_autoload_register(array('P3\AutoLoader', 'load_missing'));
	}

	private static function _register_error_handler()
	{
		if(!self::production()) {
			ini_set('display_errors', 'true');
			error_reporting(E_ALL);
		}
	}

	private static function _set_include_path()
	{
		set_include_path(implode(PATH_SEPARATOR, array(
			get_include_path(),
			ROOT.'/vendor',
			ROOT.'/lib',
			PATH.'/helper/helpers',
			ROOT.'/app/models',
			ROOT.'/app/controllers',
			ROOT.'/app/mailers'
		)));
	}
}

?>