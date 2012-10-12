<?php

namespace P3\ActionController;
use       P3\Net\Http\Request;
/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \P3\Controller\Base
{
	private static $_status = 0;

	protected $_layout;
	protected $_responses = [];
	protected $_request;
	protected $_route;
	protected $_vars = array();

//- Public
	public function __construct($request = null, $route = null)
	{
		$this->_request = !is_null($request) ? $request : \P3::request();
		$this->_route   = $route;
	}

	public function get_request()
	{
		return $this->_request;
	}

	public function process($action)
	{
		$filter_ret = $this->_before_filter();
		$filter_success = is_null($filter_ret) ? true : (bool)$filter_ret;

		// only try to process if the before_filter didn't supply a response
		if(!count($this->_responses)) {
			$return = parent::process($action);

			if(is_null($return)) {
				if(!count($this->_responses))
					$this->render_template($action);
			} else {
				if(is_array($return) && count($return) == 3)
					$return = new Response($this, $return[2], $return[1], $return[0]);

				if(!is_a($return, 'P3\Net\Http\Response'))
					throw new Exception\UnknownResponse($return, $action, $this->_request->format());

				$this->_responses[] = $return;
			}
		}

		if(!($c = count($this->_responses)))
			throw new Exception\NoRender(get_called_class(), $action, $this->_request->format());
		else if($c > 1)
			throw new Exception\MultipleRender(get_called_class(), $action, $this->_request->format());

		$this->_responses[0]->send();

		$this->_after_filter();
	}

	//TODO:  Make redirect support all kinds of stuff, like redirecting to same action in controller
	public function redirect($where)
	{
		if(is_string($where)) {
			$path = $where == ':back' ? $_SERVER['HTTP_REFERER'] : $where;
		}

		$this->_responses[] = new Response($this, null, ['Location: '.$path], Response::STATUS_MOVED);
	}

	public function render(array $options = array())
	{
		if(isset($options['template']))
			$http_body = $this->render_template($options);

		if(!isset($http_body))
			throw new Exception\InvalidRender('%s called render(), but no valid options were given', array(get_called_class()));

		$this->_responses[] = new Response($this, $http_body);
	}

	public function render_template($template, array $options = array())
	{
		$headers = isset($options['headers']) ? $options['headers'] : [];

		$this->_responses[] = Response::from_array([200, $headers, $this->render_template_to_s($template, $options)]);
	}

	public function render_template_to_s($template, array $options = array())
	{
		$view = new ActionView($this, $template);
		$view->assign($this->_vars);

		$layout = isset($options['layout']) ? $options['layout'] : $this->_layout;
		
		if($layout)
			$view->init_layout($this->_layout);

		return $view->render();
	}

	public function respond_to(callable $closure)
	{
		$responder = new FormatResponder($this);
		$closure($responder);

		$this->_responses[] = $responder->get_response($this->get_request());
	}

	public function route()
	{
		return $this->_route;
	}

	public function set_request($request)
	{
		$this->_request = $request;
	}

	public function template_exists($template = null)
	{
		if(is_null($template))
			$template = $this->_request->action;

		$view = new ActionView($this, $template);
		return $view->exists();
	}

//- Protected
	protected function _after_filter()
	{
		$response = $this->_responses[0];

		$time = (microtime(true) - \P3\START_TIME) * 1000;

		\P3::logger()->info(sprintf('Completed in %.02fms | %s %s [%s]', $time, $response->code(), $response->message(), $this->_request->url()));
	}
	
	/**
	 * @todo log params
	 */
	protected function _before_filter()
	{
		\P3::logger()->info(sprintf('Processing %s#%s (for %s at %s) [%s]', get_called_class(), $_GET['action'], $_SERVER['REMOTE_ADDR'], date('c'), strtoupper($this->_request->method())));
	}

//- Static
	public static function dispatch(Request $request)
	{
		$route = \P3::router()->route_for_request($request);
		$route->sanitize();

		$_GET = array_merge($route->options_except(array('name', 'to')), $_GET);

		$class = \str::to_camel($route->controller()).'Controller';
		$controller =  new $class($request, $route);
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