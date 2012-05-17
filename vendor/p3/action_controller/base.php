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

	protected $_layout;
	protected $_response;
	protected $_request;
	protected $_vars = array();

//- Public
	public function __construct($request = null)
	{
		$this->_request = !is_null($request) ? $request : \P3::request();
	}

	public function get_request()
	{
		return $this->_request;
	}

	public function process($action)
	{
		$return = parent::process($action);
		
		if(is_null($this->_response))
			throw new Exception\NoRender(get_called_class(), $action, $this->_request->format());

		return $this->_response;
	}

	public function render(array $options = array())
	{
		if(isset($options['template']))
			$http_body = $this->render_template($options);

		if(!isset($http_body))
			throw new Exception\InvalidRender('%s called render(), but no valid options were given', array(get_called_class()));

		$this->_response = new Response($this, $http_body);
	}

	public function render_template($template, array $options = array())
	{
		$view = new ActionView($this);
		$view->assign($this->_vars);
		
		if($this->_layout)
			$view->init_layout($this->_layout);

		return $view->render($template, $options);
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

//- Protected

//- Static
	public static function dispatch(Request $request)
	{
		$route = \P3::router()->route_for_request($request);
		$route->sanitize();

		$_GET = array_merge($route->options_except(array('name', 'to')), $_GET);

		$class = \str::to_camel($route->controller()).'Controller';
		$controller =  new $class($request);
		$controller->process($route->action());
	}

//- Magic
	public function __get($var)
	{
		if(!isset($this->_vars[$var]))
			throw new Exception\VarNoExist('%s was never set, but was attempted access', array($var));

		return $this->_vars[$var];
	}

	public function __set($var, $val)
	{
		$this->_vars[$var] = $val;
	}
}

?>
