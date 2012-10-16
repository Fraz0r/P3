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
	//Todo:  This needs to be a lot better...
	public static function for_model($for_model)
	{
		if(is_array($for_model)) {
			$model = array_pop($for_model);

			$base = get_class($model);

			if($model->is_new())
				$base = str::pluralize($base);

			$path = implode('_', $for_model).'_'.str::from_camel($base);

			if(!$model->is_new())
				return static::{$path.'_path'}($model->id());
		} else {
			return $for_model;
		}

		return static::{$path.'_path'}();
	}

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