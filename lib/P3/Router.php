<?php

/**
 * Base Routing class for P3
 *
 * @author Tim <tim.frazier@gmail.com>
 */
class P3_Router {

	/**
	 * List of routes in order of priority
	 * @var array
	 */
	private $_routes = array();

	/**
	 * Adds route to parse list
	 *
	 * @param string $path Path string
	 * @param array $route Routing Data
	 */
	public static function addRoute($path, array $route)
	{
		$o = new stdClass();
		$o->path = $path;
		$o->route = $route;

		self::$_routes[count(self::$_routes)] = $o;
	}

	/**
	 * Returns routing data for a passed path
	 *
	 * @param string $path Path to parse
	 * @return object Routing Data
	 */
	public static function route($path)
	{
		return $o;
	}

}
?>