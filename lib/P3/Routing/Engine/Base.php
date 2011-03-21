<?php

namespace P3\Routing\Engine;
use       P3\Loader;

/**
 * P3\Routing\Engine
 *
 * This is the base model for for P3\Router.  Handles Interpreting and dispatching
 * P3\Routing\Route's
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base {
	/**
	 * Container for dispatched route
	 * @var P3\Routing\Route
	 */
	private static $_dispatchedRoute = null;

	/**
	 * Holds routing_data for global route (if any)
	 * @var P3\Routing\Route
	 */
	private static $_globalRoute = null;

	/**
	 * Routing Map for router
	 * @var \P3\Routing\Map
	 */
	private static $_map = null;

	/**
	 * List of routes in order of priority
	 * @var array
	 */
	private static $_routes = array(
		'any' => array(),
		'get' => array(),
		'put' => array(),
		'post' => array(),
		'delete' => array(),
	);

	/**
	 * Adds route to parse list
	 *
	 * @param P3\Routing\Route $route Route to add to self for later interpretation
	 *
	 * @return void
	 */
	public static function add($route)
	{
		self::$_routes[$route->getMethod()][] = $route;
	}

	/**
	 * Dispatches passed URI to the proper controller/action
	 *
	 * @param string $path URI for dispatch
	 *
	 * @return P3\Routing\Route Dispatched Route
	 */
	public static function dispatch($path = null)
	{

		$route = self::getRoute($path);

		if(!$route) throw new \P3\Exception\RoutingException('No route was matched', array(), 404);

		if(defined('\APP\START_TIME'))
			define('APP\DISPATCH_TIME', microtime(true));

		self::$_dispatchedRoute = $route;

		$route->dispatch();

		return $route;
	}

	/**
	 * Returns current dispatched action
	 *
	 * @return string Current routed action
	 */
	public static function getAction()
	{
		return self::$_dispatchedRoute->getAction();
	}

	/**
	 * Returns all routes, no matter the HTTP Method
	 *
	 * @return P3\Routing\Route[]
	 */
	public static function getAllRoutes()
	{
		return array_merge(self::$_routes['any'], self::$_routes['get'], self::$_routes['put'], self::$_routes['post'], self::$_routes['delete']);
	}

	/**
	 * Returns routes filtered by HTTP Method
	 *
	 * @return P3\Routing\Route[]
	 */
	public static function getFilteredRoutes($method = 'any')
	{
		return $method == 'any' ? self::getAllRoutes() : array_merge(self::$_routes[$method], self::$_routes['any']);
	}

	/**
	 * Returns current dispatched route
	 * @return P3\Routing\Route
	 */
	public static function getDispatched()
	{
		return self::$_dispatchedRoute;
	}

	/**
	 * Returns first global route
	 *
	 * Note: Global, meaning setting both an action && a controller
	 *
	 * @return \stdClass Global Route
	 */
	public static function getGlobalRoute()
	{
		return self::$_globalRoute;
	}

	/**
	 * Returns routing map
	 *
	 * @return P3\Routing\Map
	 */
	public static function getMap()
	{
		if(is_null(self::$_map)) self::$_map = new \P3\Routing\Map;
		return self::$_map;
	}

	/**
	 * Returns first matched route for given controller/action
	 *
	 * @param string $path URI for routing
	 *
	 * @return \P3\Routing\Route Route
	 */
	public static function getRoute($path = null)
	{
		$path = !is_null($path) ? $path : (Loader::isCli() ? '/' : $_SERVER['REQUEST_URI']);

		return self::matchRoute($path);
	}

	/**
	 * Determines whether or not the request stemmed from an AJAX Request
	 *
	 * @return boolean True if AJAX, false otherwise
	 */
	public static function isXHR()
	{
		return(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	/**
	 * Finds and returns route matching path
	 *
	 * @param string $path Path to match
	 *
	 * @return \P3\Routing\Route
	 */
	public static function matchRoute($path)
	{
		$routes = self::getFilteredRoutes(strtolower($_SERVER['REQUEST_METHOD']));

		$match = false;
		$len   = count($routes);

		if(!$len) return false;

		foreach($routes as $route) {
			if(FALSE !== ($match = $route->match($path)))
				break;
		}
		
		return $match;
	}

	/**
	 * Counts and returns number of Routes
	 *
	 * @return int Number of Routes
	 *
	 * @todo fix numRoutes
	 */
	public static function numRoutes()
	{
		return count(self::$_routes);
	}

	/**
	 * URL Redirect [302]
	 *
	 * Note:  This is basic for now.  Will come back and add route based redirects
	 *
	 * @param string $path Path to redirect to
	 */
	public static function redirect($path) {
		header("Location: {$path}");
		exit;
	}

	/**
	 * Does the opposite of getRoute.  This will return a Route based on the desired
	 * controller, action, and method
	 *
	 * @param string $controller Controller to match
	 * @param string $action Action to match
	 * @param string $method Method to match
	 *
	 * @return P3\Routing\Route Returns Route if succesful, false otherwise
	 */
	public function reverseLookup($controller, $action = 'index', $method = 'any')
	{
		if(is_null($controller))
			throw new \P3\Exception\RoutingException("You asked me to look for a route with a <null> contoller?");

		$routes = self::getFilteredRoutes($method);

		$match  = false;
		$len   = count($routes);

		if(!$len) return false;

		foreach($routes as $route)
			if(FALSE !== ($match = $route->reverseMatch($controller, $action, $method)))
				break;

		return $match;
	}

	/**
	 * Sets map for the router
	 * @param \P3\Routing\Map $map Map to use for router
	 */
	public static function setMap($map)
	{
		self::$_map = $map;
	}

}

?>
