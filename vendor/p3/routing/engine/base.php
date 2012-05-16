<?php

namespace P3\Routing\Engine;
use       P3\Routing\Route;
use       P3\Routing\Exception;

require(\P3\PATH.'/routing/route.php');

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base 
{
	private static $_routes = array();

//- Public
	public function add(Route $route)
	{
		array_push(self::$_routes, $route);

		return $route;
	}

	public function routes()
	{
		return self::$_routes;
	}

	public function route_for_request($request = null)
	{
		return self::_route_for_request($request);
	}

//- Public Static
	public static function dispatch($request = null)
	{
		if(is_null($request))
			$request = \P3::request();

		return \P3\ActionController\Base::dispatch($request);
	}

	protected static function _route_for_request($request = null)
	{
		if(is_null($request))
			$request = \P3::request();

		$had_match = false;
		$match = false;
		$i     = 0;
		$j     = count(self::$_routes);

		if(!$j)
			throw new Exception\NoRoutes;

		while(!$match || !$match->valid($request->method())) {
			while($i < $j) {
				$match = self::$_routes[$i++]->match($request);

				if($match) {
					$had_match = $match;
					break;
				}
			}

			if($i >= $j && !$match) {
				$e = $had_match ? 
					  new Exception\MethodNotAllowed($had_match, $request->method())
					: new Exception\NoRouteMatched;

				throw $e;
			}
		}

		return $match;
	}
}

?>