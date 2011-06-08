<?php

/* Global P3 Constants */
namespace P3 
{
	const VERSION = '1.1.0';
}

namespace P3\ActiveRecord\Collection
{
	const FLAG_SINGLE_MODE   = 1;
	const FLAG_DYNAMIC_TYPES = 2;

	const STATE_STARTED  = 1;
	const STATE_COMPLETE = 2;
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

		/**
		 * Boots app
		 * 
		 * @return void
		 */
		public static function boot()
		{
			P3\Loader::loadEnv();
			P3\Router::dispatch();
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
