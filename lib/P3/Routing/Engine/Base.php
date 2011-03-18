<?php
/**
 * Description of Engine
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\Routing\Engine;

use P3\Loader;

abstract class Base {
	/**
	 * Container for dispatched route
	 */
	private static $_dispatchedRoute = null;

	/**
	 * Holds routing_data for global route (if any)
	 * @var stdClass $routing_data
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
	 */
	public static function add($route)
	{
		self::$_routes[$route->getMethod()][] = $route;
	}

	/**
	 * Dispatches passed URI to the proper controller/action
	 *
	 * @param string $path URI for dispatch
	 */
	public static function dispatch($path = null)
	{
		if(defined('\APP\START_TIME'))
			define('APP\DISPATCH_TIME', microtime(true));

		$route = self::getRoute($path);

		if(!$route) throw new \P3\Exception\RoutingException('No route was matched', array(), 404);
		$route->dispatch();

		self::$_dispatchedRoute = $route;
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
	 * Returns current dispatched action
	 *
	 * @return string Current routed action
	 */
	public static function getAction()
	{
		return self::$_dispatchedRoute['action'];
	}

	/**
	 * Returns current dispatched controller
	 *
	 * @return string current dispatched controller
	 */
	public static function getController()
	{
		return self::$_dispatchedRoute['controller'];
	}

	/**
	 * Returns current dispatched route
	 * @return string
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
	 * @return string Map
	 */
	public static function getMap()
	{
		if(is_null(self::$_map)) self::$_map = new \P3\Routing\Map;
		return self::$_map;
	}

	public static function isXHR()
	{
		return(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	/**
	 * Loads two common routes, one for the default ("/") route, and another for the standard MVC layout ("/:controller/:action/:id")
	 *
	 * Note:  You'll want this to be the last call in your routes.php file, otherwise they will override your other calls
	 * @param array $default_routing_data Routing data for the default route ("/").  Defaults to array('controller' => 'default');
	 */
	public static function loadDefaultRoutes(array $default_routing_data = array())
	{
		$default_routing_data = !count($default_routing_data) ? array('controller' => 'default') : $default_routing_data;
		self::addRoute("/", $default_routing_data);
		self::addRoute('/:controller[/:id]/:action');
	}


	public static function getAllRoutes()
	{
		return array_merge(self::$_routes['any'], self::$_routes['get'], self::$_routes['put'], self::$_routes['post'], self::$_routes['delete']);
	}

	/**
	 * Returns first matched route for given controller/action
	 *
	 * @param string $path URI for routing
	 * @return \P3\Routing\Route Route 
	 */
	public static function getRoute($path = null)
	{
		$path = !is_null($path) ? $path : (Loader::isCli() ? '/' : $_SERVER['REQUEST_URI']);

		return self::matchRoute($path);
	}

	public static function getFilteredRoutes($method = 'any')
	{
		return $method == 'any' ? self::getAllRoutes() : array_merge(self::$_routes[$method], self::$_routes['any']);
	}

	public static function numRoutes()
	{
		return count(self::$_routes);
	}

	/**
	 * Finds and returns route matching path
	 * @param string $path Path to match
	 * @return \P3\Routing\Route
	 */
	public static function matchRoute($path)
	{
		$routes = self::getFilteredRoutes(strtolower($_SERVER['REQUEST_METHOD']));

		$match = false;
		$len   = count($routes);

		if(!$len) return false;

		foreach($routes as $route)
			if(FALSE !== ($match = $route->match($path))) break;
		
		return $match;
	}

	public function reverseLookup($controller, $action = 'index', $method = 'any')
	{
		$routes = self::getFilteredRoutes($method);

		$match  = false;
		$len   = count($routes);

		if($len) return false;

		foreach($routes as $route)
			if(FALSE !== ($match = $route->reverseMatch($controller, $action, $method))) break;

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
