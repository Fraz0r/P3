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

		$this->_before_filter();

		$ret = call_user_func(array($this, $action));

		$this->_after_filter();

		return $ret;
	}

//- Protected
	protected function _after_filter()
	{ }
	
	protected function _before_filter()
	{ }

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