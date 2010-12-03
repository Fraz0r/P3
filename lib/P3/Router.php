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
	private static $_routes = array();

	/**
	 * (RegEx)  Used to split paths into tokens
	 * @var string
	 */
	private static $_regexTokenizer = '([^\.^/^-]*)([\./-]*)';

	/**
	 * Adds route to parse list
	 *
	 * @param string $path Path string
	 * @param array $options Routing Data
	 */
	public static function addRoute($path, array $options = array())
	{
		//$m = preg_split(sprintf('![\.-/]!', self::$_pathTokenSeperator), trim($path, '/'));


		$o = new stdClass();
		$o->path    = $path;
		$o->tokens  = self::tokenizePath($path);
		$o->options = $options;

		self::$_routes[count(self::$_routes)] = $o;
	}

	/**
	 * Dispatches passed URI to the proper controller/action
	 *
	 * @param P3_Uri $uri URI for dispatch
	 * @since 0.9.0
	 */
	public static function dispatch(P3_Uri $uri = null)
	{
	}

	/**
	 * Returns first matched route for given controller/action
	 *
	 * @param string $path URI for routing
	 * @return string Route for controller/action
	 */
	public static function routeFor($path)
	{
		/* Tokenize path for comparing */
		$path = self::tokenizePath($path);

		/* Setup routing vars */
		$dir        = "";
		$controller = null;
		$action     = null;

		/* Loop through Routes */
		foreach(self::$_routes as $r) {
			echo "Checking Route: {$r->path}\n";
			/* Grab the tokens from the route */
			$route = $r->tokens;

			/* Make sure token counts match */
			if(count($path) != count($route)) {
				continue;
			}
			/* Make sure token spereators match */
			if(count(array_diff($route[2], $path[2]))) {
				continue;
			}

			/* Potential match, loop through tokens and verify */
			for($i = 0; $i < count($path); $i++) {
				switch($route[1][$i]) {
					case ':controller':
						$controller = $path[1][$i];
						break;
					case ':action':
						$action = $path[1][$i];
						break;
					case ':dir':
						$dir = $path[1][$i];
						break;
					default:
						if(preg_match('!^:(.*)!', $route[1][$i], $m)) {
							/* TODO: Parse Arg in Router  */
							echo "Loading Param '{$m[1]}' with value '{$path[1][$i]}'\n";
						} else {
							if($route[1][$i] != "") {
								/* Static - Must match exact */
								if($path[1][$i] !== $route[1][$i]) {
									continue 3;
								}
							}
						}
						break;
				}
			}

			/* If we got this far, the route matches */
			$action     = !is_null($r->options['action']) ? $r->options['action'] : $action;
			$controller = !is_null($r->options['controller']) ? $r->options['controller'] : $controller;
			$dir        = !is_null($r->options['dir']) ? $r->options['dir'] : $dir;

			/* Default to index if we have no action */
			$action     = is_null($action) ? 'index' : $action;
			break;

		}

		/* Raise Exception if we have no controller to route too */
		if(is_null($controller)) {
			throw new P3_Exception("P3_Router:  No controller was matched in the route.", '', 500);
		}

		return array(
			'controller' => $controller,
			'action'     => $action,
			'dir'        => $dir
		);
	}

	public static function tokenizePath($path)
	{
		preg_match_all(sprintf("!%s!", self::$_regexTokenizer), trim($path, '/'), $m);
		return $m;
	}

}
?>