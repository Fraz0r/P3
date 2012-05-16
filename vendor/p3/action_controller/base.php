<?php

namespace P3\ActionController;
use       P3\Net\Http\Request;
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
	protected $_request;

//- Public
	public function get_request()
	{
		return $this->_request;
	}

	public function process($action)
	{
		$return = parent::process($action);
		
		if(is_null($this->_response))
			throw new Exception\NoRender(get_called_class());

		return $this->_response;
	}

	public function render(array $options = array())
	{
		if(isset($options['action']) || isset($options['controller'])) {
			if(isset($options['controller'])) {
				$class      = $options['controller'];
				$controller = new $class;
			} else {
				$controller = $this;
			}

			call_user_func(array($controller, $options['action']));
			$options['template'] = $options['action'];
		}
	}

	public function respond_to($closure)
	{
		$responder = new FormatResponder($this);
		$closure($responder);

		$this->_response = $responder->get_response($this->get_request());
	}

	public function set_response($response)
	{
		$this->_response = $response;
	}

	public function set_request($request)
	{
		$this->_request = $request;
	}

//- Static
	public static function dispatch(Request $request)
	{
		$route = \P3::router()->route_for_request($request);
		$route->sanitize();

		$_GET = array_merge($route->options_filtered(array('controller', 'action', 'format')), $_GET);

		$class = \str::toCamelCase($route->controller()).'Controller';
		$controller =  new $class();
		$controller->set_request($request);
		$controller->process($route->action());
	}
}

?>
