<?php

/**
 * Base Routing class for P3
 *
 * @author Tim <tim.frazier@gmail.com>
 */

namespace P3;

abstract class Router {

	/**
	 * Container for dispatched route
	 *
	 * @var array $_routing_data Associative data containing dispatched route info
	 */
	private static $_dispatchedRoute = null;

	/**
	 * Holds routing_data for global route (if any)
	 * @var stdClass $routing_data
	 */
	private static $_globalRoute = null;

	/**
	 * List of routes in order of priority
	 * @var array
	 */
	private static $_routes = array();

	/**
	 * (RegEx)  Used to split paths into tokens
	 * @var string
	 */
	private static $_regexTokenizer = '(\[?[\./-]*)([^\[^\]^\.^/^-]*)\]?';

	/**
	 * Adds route to parse list
	 *
	 * @param string $path Path string
	 * @param array $options Routing Data
	 */
	public static function addRoute($path, array $options = array(), array $specific_options = array())
	{
		$o = new \stdClass();
		$o->path    = $path;
		$o->tokens  = self::tokenizePath($path);
		$o->options = $options;
		$o->specific_options = $specific_options;

		if(is_null(self::$_globalRoute) && (FALSE !== strpos($path, ':controller') && FALSE !== strpos($path, ':action'))) {
			self::$_globalRoute = $o;
		}

		self::$_routes[count(self::$_routes)] = $o;
	}

	/**
	 * Dispatches passed URI to the proper controller/action
	 *
	 * @param string $path URI for dispatch
	 * @since 0.9.0
	 */
	public static function dispatch($routing_data = null)
	{
		$routing_data = !is_array($routing_data) ? self::parseRoute($routing_data) : $routing_data;
		self::$_dispatchedRoute = $routing_data;
		Loader::loadController($routing_data['controller'], $routing_data);
	}

	/**
	 * Renders bases on options (Partial or full loads)
	 *
	 * @param array $options
	 * @return null
	 */
	public static function render($options = null)
	{
		$options = is_null($options) ? self::parseRoute() : $options;

		if(isset($options['controller'])) {
			self::dispatch($options);
		} elseif(isset($options['partial'])) {
			$partial = $options['partial'];
			if(isset($options['locals'])) {
			 extract($options['locals']);
			}
			if(strpos($partial, '/')) {
				list($controller, $view) = explode('/', $partial);
				$view = '_'.$view.'.tpl';
			} else {
				$controller = Router::getController();
				$view = '_'.$partial.'.tpl';
			}

			$path = $controller.'/'.$view;
			require(APP_PATH.'/views/'.$path);
		}
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

	/**
	 * Returns first matched route for given controller/action
	 *
	 * @param string $path URI for routing
	 * @return string Route for controller/action
	 */
	public static function parseRoute($path_str = null)
	{
		//header("Content-type: text/plain");
		$path_str = !is_null($path_str) ? $path_str : (Loader::isCli() ? '/' : $_SERVER['REQUEST_URI']);

		/* Tokenize path for comparing */
		$path = self::tokenizePath($path_str);

		/* Setup routing vars */
		$dir        = "";
		$controller = null;
		$action     = null;
		$args       = array();
		$arg_c      = 0;

		/* Loop through Routes */
		foreach(self::$_routes as $r) {
			/* Grab the tokens from the route */
			$route = $r->tokens;

			/* First token check, fixes default route ["/"] */
			if($route[0][0] == "/" && $path[0][0] != "/") {
				continue;
			}

			/****   Taking this check out, it's not necessary *****/
			/* Make sure token spereators match 
			for($x = 0; $x < count($route[1]) -1; $x++) {
				if($route[1][$x] != $path[1][$x]) {
					if(($route[1][$x] == '[/')) {
						break 2;
					}
				}
			}
			 *
			 */

			/* Potential match, loop through tokens and verify */
			$j = 0;
			for($i = 0; $i < count($route[2]) - 1; $i++) {
				switch($route[2][$i]) {
					case ':controller':
						$controller = $path[2][$j];
						break;
					case ':action':
						$action = $path[2][$j];
						break;
					case ':dir':
						$dir = $path[2][$j];
						break;
					case ':id':
						if(!intval($path[2][$j]) && $route[0][$j] == '[/:id]') {
							$j--;
							continue;
						}
					default:
						if(preg_match('!^:(.*)!', $route[2][$j], $m)) {
							$args[$arg_c++] = $path[2][$j];
							$args[$m[1]] = $path[2][$j];
						} else {
							if($route[2][$j] != "") {
								/* Static - Must match exact */
								if($path[2][$j] !== $route[2][$j]) {
									continue 3;
								}
							}
						}
						break;
				}
			$j++;
			}

			if(isset($r->specific_options['only'])) {
				if(!in_array($action, $r->specific_options['only'])) {
					continue;
				}
			}


			/* Parse args */
			while($i < count($path[2]) -1 ) 
				$args[$arg_c++] = $path[2][$i++];

			/* If we got this far, the route matches */
			$action     = isset($r->options['action'])    && !is_null($r->options['action'])     ? $r->options['action']     : $action;
			$controller = isset($r->options['controller']) && !is_null($r->options['controller']) ? $r->options['controller'] : $controller;
			$dir        = isset($r->options['dir'])       && !is_null($r->options['dir'])        ? $r->options['dir']        : $dir;

			/* Default to index if we have no action, to show if we have no action but an id */
			if(empty($action)) {
				if(!isset($args['id']) || empty($args['id'])) {
					$action = 'index';
				} else {
					$action = 'show';
				}
			}
			break;

		}

		/* Raise Exception if we have no controller to route too */
		if(is_null($controller)) {
			throw new Exception\RoutingException("Router:  No controller was matched in the route.", '', 501);
		}

		return array(
			'controller' => $controller,
			'action'     => $action,
			'path'       => $path,
			'dir'        => $dir,
			'args'       => $args
		);
	}

	/**
	 * Returns path in the form of tokens, usable by internal methods
	 *
	 * @param string $path Path to tokenize
	 * @return array Tokens from passed path
	 */
	public static function tokenizePath($path)
	{
		if($path != '/')
			$path = rtrim($path, '/');

		preg_match_all(sprintf("!%s!", self::$_regexTokenizer), $path, $m);
		return $m;
	}

}
?>