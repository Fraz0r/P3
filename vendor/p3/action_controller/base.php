<?php

namespace P3\ActionController;
use       P3\Routing\Route;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \P3\Controller\Base
{
	private static $_status = 0;

	protected $_response;

//- Public
	public function process($action)
	{
		$return = parent::process($action);
		
		if(is_null($this->_response))
			throw new Exception\NoRender(get_called_class());
	}

	public function render($what, array $options = array())
	{
		if(!is_array($what))
			$what = array('template' => $what);

		$type = key($what);
		$val  = current($what);

		call_user_func_array(array($this, 'render_'.$type), array($val, $options));
	}

	public function respond_to($closure)
	{
	}

//- Static
	public static function dispatch(Route $route)
	{
		$route->sanitize();

		$_GET = array_merge($route->options_except(array('conditions', 'to', 'name')), $_GET);

		$response = parent::dispatch($route->controller(), $route->action());
	}
}

?>
