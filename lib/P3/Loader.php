<?php

/**
 * P3_Loader
 *
 * Handles loading anything and everything throughout P3
 */
class P3_Loader
{
	/**
	 * Magic AutoLoad function attems to Load class when
	 * Calling, or Instantiating an unloaded Class
	 *
	 * @param string $class Class being searched for by PHP
	 */
	public static function autoload($class){
		self::loadClass($class);
	}

	/**
	 * Safe way to check if class exists (without autoload)
	 *
	 * @param string class Class to see if loaded
	 * @param bool $autoload whether or not to try and autoload it
	 * @return bool Class Exists
	 */
	public static function classExists($class, $autoload = false)
	{
		return class_exists($class, $autoload);
	}

	/**
	 * Create a link based from (controller, action)
	 * Including Defined Path Prefix
	 *
	 * @param array $location
	 * @return string
	 */
	public static function createURI(array $location = null, array $arguments = null, array $get = array())
	{
		if($location == null) {
			return '/'.P3_PATH_PREFIX;
		} else {
			$controller = $location[0];
			if(count($location) == 2) {
				$action = $location[1].'/';
			} else {
				$action = '';
			}

			if(count($get)) {
				$get_args = array();
				foreach($get as $k => $v) {
					$get_args[] = "{$k}={$v}";
				}
			}

			return '/'.P3_PATH_PREFIX.$controller.'/'.$action.(($arguments != null) ? implode('/', $arguments) : '').((!empty($get_args) ? '?'.implode('&', $get_args) : ''));
		}
	}

	/**
	 * Loads appropriate controller based on Uri
	 *
	 * @param P3_Uri $uri URI to Dispatch
	 * @deprecated since version 0.9.0
	 * @see P3_Router::dispatch()
	 */
	public static function dispatch(P3_Uri $uri = null){
		if(is_null($uri)) {
			$uri = new P3_Uri;
		}

		self::loadController($uri->getController(), $uri);
	}

	/**
	 * Returns either the basic loader, or Cli_Loader (If Cli Mode)
	 */
	public static function getLoader()
	{
		return self::isCli() ? 'P3_Cli_Loader' : 'P3_Loader';
	}

	/**
	 * Returns true if being run from terminal, false if from apache
	 *
	 * @return bool is being run from terminal
	 */
	public static function isCli()
	{
		return(defined('STDIN'));
	}

	/**
	 * Load Bootstrap into application
	 *
	 * @param string $file Bootstrap File
	 */
	public static function loadBootstrap($file = null){
		if(empty($file)){
			if(!defined('P3_APP_PATH'))
				throw new P3_Exception('P3_APP_PATH not defined, cannot locate bootstrap.');
			else
				$file = P3_APP_PATH.'/bootstrap.php';
		}

		if(!is_readable($file))
			throw new P3_Exception('Unable to load bootstrap file "%s"', array($file));

		require($file);
	}

	/**
	 * Loads P3_Controller
	 *
	 * @param string $controller
	 * @param P3_URI $uri
	 * @return <type>
	 */
	public static function loadController($controller, P3_URI $uri)
	{
		$name  = strtolower($controller);
		$class = P3_String_Utils::to_camel_case($controller, true).'Controller';

		if(self::classExists($class)) return;

		$path = P3_APP_PATH.'/controllers/';
		if(self::isCli()) {
			$path .= 'cli/';
		}

		if(is_readable($path.$name.'.php')) {
			include_once($path.$name.'.php');
		} else {
			throw new P3_Exception('The controller "%s" failed to load', array($class), 404);
		}

		if(!self::classExists($class)) {
			throw new P3_Exception('The controller "%s" failed to load', array($class), 500);
		}

		return(new $class($uri));
	}

	/**
	 * Loads a class into the Application
	 *
	 * @param string $class
	 * @return void
	 */
	public static function loadClass($class){
		/**
		 *  If class is already included, exit
		 */
		if(class_exists($class, false) || interface_exists($class, false))
			return;

		/**
		 * Attempt to Load Class File
		 */

		include_once(self::getClassPath($class));

		/**
		 *  If class still doesn't exist, we have a problem
		 */
		if(!class_exists($class, false) && !interface_exists($class, false))
			throw new P3_Exception('The class "%s" failed to load', array($class));

	}

	public static function loadEnv()
	{
		/* Attempt to set up an app path if we dont have one */
		if(!defined("P3_APP_PATH")) {
			define("P3_APP_PATH", realpath(dirname(__FILE__).'/../..').'/app');
		}

		/* Make sure lib is include path */
		set_include_path(realpath(dirname(__FILE__).'/..').PATH_SEPARATOR.get_include_path());

		/* Set up Auto Loading */
		self::registerAutoload();
	}

	/**
	 * Loads helpers into system
	 */
	public static function loadHelpers()
	{
		/* @todo: Find a better way than including as they are built.. maybe loop through P3/Helpers? */
		$path = 'P3/Helpers/';
		require_once($path.'html.php');
		require_once($path.'number.php');
	}

	/**
	 * Loads a Model into the application
	 *
	 * @param string $model Model Being Loaded
	 */
	public static function loadModel($model)
	{
		if(self::classExists($model)) {
			return;
		}

		$path = P3_APP_PATH.'/models/'.$model.'.php';

		if(!is_readable($path)) {
			throw new P3_Exception('Couldn\'t read Model "%s" into the system', array($model));
		}

		require_once($path);
	}

	/**
	 * Replaces the '_' in a classes name with '/' and returns it
	 * Tricky =]
	 *
	 * @param string $class ClassName
	 * @return string Classes relative path (from include path)
	 */
	public static function getClassPath($class){
		require_once('P3/String/Utils.php');

		$exp = explode('_', $class);
		$file = ucfirst(P3_String_Utils::to_camel_case(array_pop($exp), true).'.php');
		$dir  = implode(DIRECTORY_SEPARATOR, $exp);

		return $dir.DIRECTORY_SEPARATOR.$file;
	}

	/**
	 * Redirects to location
	 *
	 * @param string,array(controller, action) $location
	 */
	public static function redirect($location = '', $hash = null)
	{
		$loc = '/'.P3_PATH_PREFIX;
		if(is_array($location)) {
			$loc .= $location[0].'/';
			if(!empty($location[1])) {
				$loc .= $location[1].'/';
			}
		} else {
			$loc .= ltrim($location, '/');
		}

		if($hash !== null) {
			$loc .= '#'.$hash;
		}

		header("Location: {$loc}");
	}

	/**
	 * Enables AutoLoading via SPL's spl_autoload_register()
	 *
	 * @param string $class AutoLoading Class [must contain autoload, like self]
	 */
	public static function registerAutoload($class = 'P3_Loader'){
		if(!function_exists('spl_autoload_register'))
			throw new P3_Exception('spl_autoload does not exist in this PHP Installation');

		self::loadClass($class);

		if(!in_array('autoload', get_class_methods($class)))
			throw new P3_Exception('The class "%s" does not have an autoload() method', array($class));

		/* Regiser AutoLoad funtion */
		spl_autoload_register(array($class, 'autoload'));
	}

}

?>
