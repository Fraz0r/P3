<?php

/**
 * Loader
 *
 * Handles loading anything and everything throughout P3
 */

namespace P3;

class Loader
{
	/**
	 * Magic AutoLoad function attems to Load class when
	 * Calling, or Instantiating an unloaded Class
	 *
	 * @param string $class Class being searched for by PHP
	 */
	public static function autoload($class){
		if(count(explode('\\', $class)) > 1) {
			self::loadClass($class);
		} elseif(ucfirst($class[0]) == $class[0]) {
			//Load Model, if first char is upperscase
			if(substr($class, -10) == "Controller") {
				require_once(APP_PATH.'/controllers/'.strtolower(substr($class, 0, -10)).'_controller.php');
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
			if(!defined('\P3\APP_PATH'))
				throw new Exception\LoaderException('APP_PATH not defined, cannot locate bootstrap.');
			else
				$file = APP_PATH.'/bootstrap.php';
		}

		if(!is_readable($file))
			throw new Exception\LoaderException('Unable to load bootstrap file "%s"', array($file));

		require($file);
	}

	/**
	 * Loads Controller
	 *
	 * @param string $controller
	 * @param array $routing_data
	 * @return Controller
	 */
	public static function loadController($controller, array $routing_data = array())
	{
		self::loadHelper('str');

		$name  = strtolower($controller);
		$class = \str::toCamelCase($controller, true).'Controller';



		if(self::classExists($class)) return;

		$path = APP_PATH.'/controllers/';

		if(is_readable($path.$name.'_controller.php')) {
			include_once($path.$name.'_controller.php');
		} else {
			throw new Exception\LoaderException('The controller "%s" failed to load', array($class), 404);
		}

		if(!self::classExists($class)) {
			throw new Exception\LoaderException('The controller "%s" failed to load', array($class), 500);
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
			throw new Exception\LoaderException('The class "%s" failed to load', array($class));

	}

	/**
	 * This function will set APP_PATH (if it's not set), update the PHP Include path (unless $set_include_path is false), register auto-load, load the bootstrap, and load the routes
	 */
	public static function loadEnv(array $options = array())
	{
		$set_include_path = isset($options['set_include_path']) ? $options['set_include_path'] : true;
		$start_session    = isset($options['start_session']) ? $options['start_session'] : true;


		/* Attempt to set up app paths if we dont have them */
		if(!defined("P3\ROOT"))     define("P3\ROOT", realpath(dirname(__FILE__).'/../..'));
		if(!defined("P3\APP_PATH")) define("P3\APP_PATH", ROOT.'/app');

		/* Include lib */
		if($set_include_path)
			set_include_path(realpath(dirname(__FILE__).'/..').PATH_SEPARATOR.get_include_path());

		/* Set up Auto Loading */
		self::registerAutoload();

		if($start_session)
			Session::singleton();

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
		$path = dirname(__FILE__).'/Helper/helpers/'.$helper.'.php';

		if(!is_readable($path)) {
			self::loadClass('\P3\Exception\LoaderException');
			throw new Exception\LoaderException('Couldn\'t read Helper "%s" into the system', array($helper));
		}

		require_once($path);

		if(!self::classExists($helper)) {
			self::loadClass('\P3\Exception\LoaderException');
			throw new Exception\LoaderException('Couldn\'t read Helper "%s" into the system', array($helper));
		}

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

			$path = APP_PATH.'/models/'.$model.'.php';

			self::loadClass('\P3\Exception\LoaderException');
			if(!is_readable($path)) {
				throw new Exception\LoaderException('Couldn\'t read Model "%s" into the system', array($model));
			}

			require_once($path);
		}
	}

	/**
	 * Loads Routes into Router
	 * @param string $file File containing routing statements.  Default path is attempted if left null.
	 */
	public static function loadRoutes($file = null)
	{
		if(is_null($file)) {
			if(!defined('\P3\APP_PATH'))
				throw new Exception\LoaderException('APP_PATH not defined, cannot locate routes.');
			else
				$file = APP_PATH.'/routes.php';
		}
		if(!is_readable($file))
			throw new Exception\LoaderException('Unable to load routes file "%s"', array($file));

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

		$exp = explode('\\', ltrim($class, '\\'));
		$file = ucfirst(\str::toCamelCase(array_pop($exp), true).'.php');
		$dir  = implode(DIRECTORY_SEPARATOR, $exp);

		$path = $dir.DIRECTORY_SEPARATOR.$file;

		return $path;
	}

	/**
	 * Enables AutoLoading via SPL's spl_autoload_register()
	 *
	 * @param string $class AutoLoading Class [must contain autoload, like self]
	 */
	public static function registerAutoload($class = '\P3\Loader'){
		if(!function_exists('spl_autoload_register'))
			throw new Exception\LoaderException('spl_autoload does not exist in this PHP Installation');

		self::loadClass($class);

		if(!in_array('autoload', get_class_methods($class)))
			throw new Exception\LoaderException('The class "%s" does not have an autoload() method', array($class));

		/* Regiser AutoLoad funtion */
		spl_autoload_register(array($class, 'autoload'));
	}

}

?>
