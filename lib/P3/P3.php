<?php


/* Global P3 Constants */

namespace P3 
{
	const VERSION = '1.0.0-experimental';
}

namespace P3\ActiveRecord\Collection
{
	const FLAG_SINGLE_MODE   = 1;
	const FLAG_DYNAMIC_TYPES = 2;

	const STATE_STARTED  = 1;
	const STATE_COMPLETE = 2;
}




namespace  //Clear Namespace from defining global constants above
{
	require_once(dirname(__FILE__).'/Loader.php');

	/**
	 * Description of P3
	 *
	 * @author Tim Frazier <tim.frazier at gmail.com>
	 */
	final class P3
	{
		private static $_env = null;

		private static $_routingClass = '\P3\Router';

		/**
		 * @var P3\Database\Base
		 */
		private static $_database = null;

		/**
		 * Boots app
		 */
		public static function boot()
		{
			P3\Loader::loadEnv();
			P3\Router::dispatch();
		}

		public static function getDatabase()
		{
			if(empty(self::$_database)) {
				self::$_database = new \P3\Database\Connection;
			}

			return self::$_database;
		}

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
		 * Returns Routing class
		 * @return string Routing class
		 */
		public static function getRouter()
		{
			return self::$_routingClass;
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
		 * Renders bases on options (Partial or full loads)
		 *
		 * @param array $options
		 *
		 * @return null
		 */
		public static function render($options = null)
		{
			if(!is_array($options)) {
				var_dump($options);
			}
		}

		public static function request()
		{
			return P3\Routed\Request::singleton();
		}

		/**
		 * Sets default routing class
		 *
		 * @param string $routingClass Class to use as default router
		 *
		 * @return void
		 */
		public static function setRouter($routingClass)
		{
			self::$_routingClass = $routingClass;
		}

		/**
		 * Determines Development Environment
		 *
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
}

?>
