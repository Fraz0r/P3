<?php

namespace P3\Controller;

/**
 * Description of controller
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base
{
//- Public
	public function process($action)
	{
		if(!method_exists($this, $action))
			throw new Exception\NoAction($this, $action);

		$ret = call_user_func(array($this, $action));

		return $ret;
	}

//- Protected

//- Public Static
	public static function dispatch($controller, $action)
	{
		$class = \str::toCamelCase($controller).'Controller';
		$controller =  new $class();

		$controller->process($action);

		return $controller;
	}
}

?>