<?php

/**
 * Base Routing class for P3
 *
 * @author Tim <tim.frazier@gmail.com>
 */
class P3_Router {

	/**
	 * Stack of routes in order of priority (FIFO)
	 * @var array
	 */
	private static $_routes = array();

	/**
	 * Regex used to split paths into tokens
	 * @var string
	 */
	private static $_regexTokenizer = '([\./-]*)([^\.^/^-]*)';

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
	public static function dispatch($path)
	{
		$route = is_array($path) ? $path : self::parseRoute($path);
	}

	/**
	 * Returns first matched route for given controller/action
	 *
	 * @param string $path URI for routing
	 * @return string Route for controller/action
	 */
	public static function parseRoute($path_str)
	{
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
			echo "Checking Route: {$r->path}\n";
			/* Grab the tokens from the route */
			$route = $r->tokens;

			/* Make sure token spereators match */
			for($x = 0; $x < count($route[1]) -1; $x++) {
				if($route[1][$x] != $path[1][$x]) 
					break 2;
			}

			/* Potential match, loop through tokens and verify */
			for($i = 0; $i < count($route[2]) - 1; $i++) {
				switch($route[2][$i]) {
					case ':controller':
						$controller = $path[2][$i];
						break;
					case ':action':
						$action = $path[2][$i];
						break;
					case ':dir':
						$dir = $path[2][$i];
						break;
					default:
						if(preg_match('!^:(.*)!', $route[2][$i], $m)) {
							$args[$arg_c++] = $path[2][$i];
							$args[$m[1]] = $path[2][$i];
						} else {
							if($route[2][$i] != "") {
								/* Static - Must match exact */
								if($path[2][$i] !== $route[2][$i]) {
									continue 3;
								}
							}
						}
						break;
				}
			}
			var_dump($i);

			/* Parse args */
			while($i < count($path[2]) -1 ) 
				$args[$arg_c++] = $path[2][$i++];

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
			throw new P3_Exception("P3_Router:  No controller was matched in the route.", '', 501);
		}

		return array(
			'controller' => $controller,
			'action'     => $action,
			'path'       => $path,
			'dir'        => $dir,
			'args'       => $args
		);
	}

	public static function tokenizePath($path)
	{
		preg_match_all(sprintf("!%s!", self::$_regexTokenizer), rtrim($path, '/'), $m);
		return $m;
	}

}
?>