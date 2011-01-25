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
		if(count(explode('_', $class)) > 1) {
			self::loadClass($class);
		} elseif(ucfirst($class[0]) == $class[0]) {
			//Load Model, if first char is upperscase
			if(substr($class, -10) == "Controller") {
				require_once(P3_APP_PATH.'/controllers/'.strtolower(substr($class, 0, -10)).'.php');
			} else {
				self::loadModel($class);
			}
		} else {
			//Load helper, if first char is lowercase
			self::loadHelper($class);
		}
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
	 * @param array $routing_data
	 * @return P3_Controller
	 */
	public static function loadController($controller, array $routing_data = array())
	{
		self::loadHelper('str');

		$name  = strtolower($controller);
		$class = str::toCamelCase($controller, true).'Controller';


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

		return new $class($routing_data);
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

	/**
	 * This function will set P3_APP_PATH (if it's not set), update the PHP Include path (unless $set_include_path is false), register auto-load, load the bootstrap, and load the routes
	 */
	public static function loadEnv(array $options = array())
	{
		$set_include_path = isset($options['set_include_path']) ? $options['set_include_path'] : true;
		$start_session    = isset($options['start_session']) ? $options['start_session'] : true;


		/* Attempt to set up app paths if we dont have them */
		if(!defined("P3_ROOT"))     define("P3_ROOT", realpath(dirname(__FILE__).'/../..'));
		if(!defined("P3_APP_PATH")) define("P3_APP_PATH", P3_ROOT.'/app');

		/* Include lib */
		if($set_include_path)
			set_include_path(realpath(dirname(__FILE__).'/..').PATH_SEPARATOR.get_include_path());

		/* Set up Auto Loading */
		self::registerAutoload();

		if($start_session)
			P3_Session::singleton();

		/* Load Bootstrap */
		self::loadBootstrap();

		/* Load Routes */
		self::loadRoutes();
	}

	/**
	 * Loads helpers into system
	 */
	public static function loadHelper($helper)
	{
		$path = dirname(__FILE__).'/Helpers/'.$helper.'.php';

		if(!is_readable($path)) {
			throw new P3_Exception('Couldn\'t read Helper "%s" into the system', array($helper));
		}

		require_once($path);
	}

	/**
	 * Loads a Model into the application
	 *
	 * @param string $model Model Being Loaded
	 */
	public static function loadModel($model)
	{
		if(!is_null($model)) {
			if(self::classExists($model)) {
				return;
			}

			$path = P3_APP_PATH.'/models/'.$model.'.php';

			if(!is_readable($path)) {
				throw new P3_Exception('Couldn\'t read Model "%s" into the system', array($model));
			}

			require_once($path);
		}
	}

	/**
	 * Loads Routes into P3_Router
	 * @param string $file File containing routing statements.  Default path is attempted if left null.
	 */
	public static function loadRoutes($file = null)
	{
		if(is_null($file)) {
			if(!defined('P3_APP_PATH'))
				throw new P3_Exception('P3_APP_PATH not defined, cannot locate routes.');
			else
				$file = P3_APP_PATH.'/routes.php';
		}
		if(!is_readable($file))
			throw new P3_Exception('Unable to load routes file "%s"', array($file));

		require($file);
	}

	/**
	 * Replaces the '_' in a classes name with '/' and returns it
	 *
	 * @param string $class ClassName
	 * @return string Classes relative path (from include path)
	 */
	public static function getClassPath($class){
		self::loadHelper('str');

		$exp = explode('_', $class);
		$file = ucfirst(str::toCamelCase(array_pop($exp), true).'.php');
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
