<?php

/**
 * Description of url
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
final class url 
{
	private static $_registered = array();

//- Public Static
	public static function register_named_route($name, $route)
	{
		self::$_registered[$name] = $route;

		return $route;
	}

//- Magic
	public static function __callStatic($method, $arguments)
	{
		if('_path'      === substr($method, -5)) {
			$parsed       = substr($method, 0, -5);
			$include_host = false;
		} elseif('_url' === substr($method, -4)) {
			$parsed       = substr($method, 0, -4);
			$include_host = true;
		} 

		if(!isset($parsed) || !isset(self::$_registered[$parsed]))
			throw new P3\Helper\Exception\MethodNotFound('url', $method);

		$route = self::$_registered[$parsed];
		$path  = $route($arguments);

		return(($include_host ? $route->host() : \p3::root_path()).$path);
	}
}

?>