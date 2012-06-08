<?php

/* Global P3 Constants */
namespace P3 
{
	const VERSION = '1.1.5';
}

namespace P3\ActiveRecord\Collection
{
	const FLAG_SINGLE_MODE   = 1;
	const FLAG_DYNAMIC_TYPES = 2;

	const STATE_STARTED  = 1;
	const STATE_COMPLETE = 2;
}

namespace P3\System\Logging
{
	const LEVEL_UNKNOWN = 100;
	const LEVEL_DEBUG   = 1;
	const LEVEL_INFO    = 2;
	const LEVEL_WARN    = 3;
	const LEVEL_ERROR   = 4;
	const LEVEL_FATAL   = 5;

	const REPORTING_LEVEL_DEV  = LEVEL_DEBUG;
	const REPORTING_LEVEL_PROD = LEVEL_INFO;
	const REPORTING_LEVEL_NONE = 200;
}

namespace P3\Mail
{
	const SEND_HANDLER_P3   = 1;
	const SEND_HANDLER_PEAR = 2;

	const FLAG_SEND_USING_SMTP = 1;
}

namespace
{
	require_once(dirname(__FILE__).'/Loader.php');

	/**
	 * Convinience class for P3
	 * 
	 * Not To be instantiated
	 * 
	 * @author Tim Frazier <tim.frazier at gmail.com>
	 * @package P3
	 * @version $Id$
	 */
	final class P3
	{
		/**
		 * Current environment
		 * 
		 * @var string
		 */
		private static $_env = null;

		/**
		 * Class to use in Routing
		 * 
		 * @var string
		 */
		private static $_routingClass = '\P3\Router';

		/**
		 * Database Connection for application
		 * 
		 * @var P3\Database\Base
		 */
		private static $_database = null;

		private static $_logger = null;

		/**
		 * Boots app
		 * 
		 * @return void
		 */
		public static function boot()
		{
			define('P3\START_TIME', microtime(true));
			P3\Loader::loadEnv();
			P3\Router::dispatch();
		}

		/**
		 * Get or set database
		 * 
		 * @param P3\Database\Connection $db
		 * @return P3\Database\Connection
		 */
		public static function database($db = null)
		{
			if(is_null($db)) {
				if(empty(self::$_database))
					self::$_database = new \P3\Database\Connection;

				return self::$_database;
			} else {
				self::$_database = $db;
			}
		}

		/**
		 * Returns name of app
		 * 
		 * 	Note:  Currently only used in logging
		 * 
		 * @return string app name 
		 */
		public static function getAppName()
		{
			return defined('APP\NAME') ? 'app['.\APP\NAME.']' : 'app';
		}

		/**
		 * Returns useable database connection - Esstablishes connection on first call
		 * 
		 * @return P3\Database\Connection
		 */
		public static function getDatabase()
		{
			if(empty(self::$_database)) {
				self::$_database = new \P3\Database\Connection;
			}

			return self::$_database;
		}

		/**
		 * Returns current development environment
		 * 
		 * @return string Development Environment
		 */
		public static function getEnv()
		{
			if(is_null(self::$_env)) self::$_env = self::_determineEnv();

			return self::$_env;
		}

		public static function getLogger()
		{
			if(is_null(self::$_logger))
				self::$_logger = new \P3\System\Logger();

			return self::$_logger;
		}

		/**
		 * Returns Routing class
		 * 
		 * @return string Routing class
		 */
		public static function getRouter()
		{
			return self::$_routingClass;
		}

		/**
		 * Checks whether or not in development
		 * 
		 * @return boolean True if in development, false otherwise
		 */
		public static function development()
		{
			return self::$_env == 'development' || self::$_env == 'dev';
		}

		/**
		 * Checks whether or not in production
		 * 
		 * @return boolean True if in production, false otherwise
		 */
		public static function production()
		{
			return self::$_env == 'production' || self::$_env == 'prod';
		}

		/**
		 * Returns convinience object w/ parse_url() functionality
		 * 
		 * @return P3\Routed\Request
		 * @see parse_url()
		 */
		public static function request()
		{
			return P3\Routed\Request::singleton();
		}

		/**
		 * Sets default routing class
		 *
		 * @param string $routingClass Class to use as default router
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

	/*  Determine current environment after loaded */
	P3::getEnv();
}

?>
