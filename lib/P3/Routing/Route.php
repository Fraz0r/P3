<?php

/**
 * Description of Route
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\Routing;

use P3\Loader;
use P3\Exception\RouteException as Exception;

class Route {
	protected $_action     = null;
	protected $_controller = null;
	protected $_map        = null;
	protected $_method     = 'any';
	protected $_options    = array();
	protected $_path       = null;
	protected $_params     = array();
	protected $_prefix     = '/';
	protected $_tokens     = array();

	/**
	 * Holds tokenized strings, so the regex only runs once (for performance)
	 * @var array
	 */
	public static $_tokenCache = array();

	protected static $_tokenRegEx = '(\[?[/\.])([^/^\.^\]^\[]*)\]?';

	public function __construct($path, $options, $method = null, $map = null)
	{
		$this->_map = $map;

		if(isset($options['method'])) {
			$this->_method = $options['method'];
		} else {
			$this->_method = is_null($method) ? 'any' : $method;
		}


		if(isset($options['to'])) {
			$ex = explode('#', $options['to']);
			if(count($ex) > 1)
				list($controller, $action) = $ex;
			else
				$controller = $ex[0];

			$this->_controller = $controller;
			$this->_action     = isset($action) ? $action : 'index';
		} else {
			$this->_controller = isset($options['controller']) ? $options['controller'] : null;
			$this->_action = isset($options['action']) ? $options['action'] : 'index';
		}

		$this->_options = $options;
		$this->_path    = rtrim($path, '/');
		$this->_tokens =  $this->_tokenize();
	}

	public function dispatch()
	{

		$this->fillGET();

		$controller_class = $this->getControllerClass();
		\P3\Loader::loadController($controller_class);
		$controller_class = '\\'.$controller_class;

		$controller = new $controller_class;

		$ret = $controller->process($this->getAction());


		if($ret !== FALSE && !$controller->rendered()) {
			$controller->render();
		}

		if(defined('\APP\START_TIME')) {
			define('APP\TOTAL_TIME',  microtime(true) - \APP\START_TIME);
		}

		if(defined('\APP\START_TIME')) {
			printf("<br /><br /><b>Dispatched In: </b>%0.4fms", (\APP\DISPATCH_TIME - \APP\START_TIME) * 1000);
			printf("<br /><br /><b>Rendered In: </b>%0.4fms", (\APP\RENDER_TIME - \APP\DISPATCH_TIME) * 1000);
			printf("<br /><br /><b>Total: </b>%0.4fms", (\APP\RENDER_TIME - \APP\START_TIME) * 1000);
		}
	}

	public function fillGET()
	{
		$_GET = array_merge(array(
			'controller' => $this->_controller,
			'action'     => $this->_action
		), $this->_params, $_GET);
	}

	public function getAction()
	{
		return $this->_action;
	}

	public function getController()
	{
		return $this->_controller;
	}

	public function getFormat()
	{
		return $this->_params['format'];
	}

	public function getNamespace()
	{
		return '';
	}

	public function getMethod()
	{
		return $this->_method;
	}

	public function getControllerClass()
	{
		return ucfirst(\str::toCamelCase($this->_controller)).'Controller';
	}

	public function getViewPath()
	{
		return $this->_controller.'/'.$this->_action;
	}

	public function match($path, $method = null)
	{

		//printf("Checking Route: [%s] %s#%s (%s)<br />", $this->_method, $this->_controller, $this->_action, $this->_path);

		$path = rtrim($path, '/');
		$method = is_null($method) ? strtolower($_SERVER['REQUEST_METHOD']) : $method;

		/* Validate Method */
		if(!$this->_validateMethod($method)) return false;

		/* Handle Default route */
		if($path == '' && $this->_path == '') return $this;

		/* Match Tokens */
		if($this->_matchTokens($path)) return $this;

		return false;
	}


	public function reverseMatch($controller, $action, $method)
	{
		//printf("Checking Route: [%s] %s#%s (%s)<br />", $this->_method, $this->_controller, $this->_action, $this->_path);
		return
			($this->_controller == $controller
				&& $this->_action == $action
				&& ($method == 'any' || $this->_method == $method)) ? $this : false;
	}


//protected
	protected function _matchTokens($path)
	{
		$self        = $this->_tokens;
		$self_tokens = $self[2];
		$self_sep    = $self[1];
		$self_len    = count($self_tokens);

		$passed        = $this->_tokenize($path);
		$passed_tokens = $passed[2];
		$passed_sep    = $passed[1];
		$passed_len    = count($passed_tokens);

		/* Cant do this anymore, added optional params */
		//if(count($self_tokens) !== $passed_len) return false;

		for($x = 0; $x < $self_len; $x++) {
			$passed_token = $passed_tokens[$x];
			$self_token   = $self_tokens[$x];

			if($self_token != $passed_token) {
				if(!preg_match('/^:([^\/]*)/', $self_token, $m)) {
					return false;
				} else {
					$this->_params[$m[1]] = $passed_token;
				}
			}
		}

		if(isset($this->_params['controller'])) $this->_controller = $this->_params['controller'];
		if(isset($this->_params['action']))     $this->_action     = $this->_params['action'];

		$this->_params['format'] = isset($this->_params['format']) ? $this->_params['format'] : 'html';

		return true;
	}

	protected function _tokenize($str = null)
	{
		$str = is_null($str) ? $this->_path : $str;

		if(isset(self::$_tokenCache[$str])) return self::$_tokenCache[$str];

		$ret = preg_match_all(sprintf('!%s!', self::$_tokenRegEx), $str, $m);

		if($ret) {
			self::$_tokenCache[$str] = $m;
			return $m;
		} else {
			return false;
		}
	}

	protected function _validateMethod($method)
	{
		return $this->_method == $method || $this->_method == 'any';
	}

//Magic
	public function __call($func, $args)
	{
		$options = isset($args[1]) ? $args[1] : array();
		$options['prefix'] = $this->_path.'/';

		return $this->_map->{$func}($args[0], $options);
	}

	public function __invoke($args)
	{
		$ret = str_replace(':id', $args[0], $this->_path);
		$ret = preg_replace('/\[.:format\]$/', '', $ret);
		return $ret;
	}
}
?>