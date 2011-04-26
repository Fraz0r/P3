<?php

namespace P3\Routed;
use       P3\Loader;
use       P3\Exception\RouteException as Exception;

/**
 * P3\Routing\Route
 *
 * A Route is a possible path to be routed into the Application.
 * P3\Routing\Engine\Base is responsible for looping through and dispatching
 * the approriate Route.
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Route {
//- attr-protected
	/**
	 * Route's Action
	 * @var string
	 */
	protected $_action     = null;

	/**
	 * Route's Controller
	 * @var string
	 */
	protected $_controller = null;

	/**
	 * Route's Map [if any]
	 * @var P3\Routing\Map
	 */
	protected $_map        = null;

	/**
	 * Route's Method
	 * @var string
	 */
	protected $_method     = 'any';

	/**
	 * Route's Options
	 * @var array
	 */
	protected $_options    = array();

	/**
	 * Route's Path
	 * @var string
	 */
	protected $_path       = null;

	/**
	 * Route's Params
	 * @var array
	 */
	protected $_params     = array();

	/**
	 * Route's Controller
	 * @var string
	 */
	protected $_prefix     = '/';

	/**
	 * Route's Tokens
	 * @var array
	 */
	protected $_tokens     = array();

//- attr-static-protected
	/**
	 *
	 * @var <type>
	 */
	protected static $_tokenRegEx = '(\[?[/\.])([^/^\.^\]^\[]*)\]?';

//- attr-static-public
	/**
	 * Holds tokenized strings, so the regex only runs once (for performance)
	 * @var array
	 */
	public static $_tokenCache = array();

//- Public
	public function __construct($path, $options, $method = null, $map = null)
	{
		$path = trim($path, '/');
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

		if(isset($options['prefix']))
			$path = trim($options['prefix'], '/').'/'.$path;

		if(isset($options['namespace']))
			$path = rtrim($options['namespace']).'/'.$path;

		$this->_options = $options;
		$this->_path    = empty($path) ? '' : '/'.rtrim($path, '/');
		$this->_tokens =  $this->_tokenize();
	}

	/**
	 * Dispatches self
	 *
	 * @return void
	 *
	 * @todo Remove Print statements from dispatch()
	 */
	public function dispatch()
	{
		$this->fillGET();

		$controller_class = $this->getControllerClass();
		\P3\Loader::loadController($controller_class);

		$controller_class = '\\'.$controller_class;

		$controller = new $controller_class;


		$ret = $controller->dispatch($this->getAction());

		if(defined('\APP\START_TIME')) {
			define("APP\DISPATCHED_IN", (\APP\DISPATCH_TIME - \APP\START_TIME) * 1000);
			define("APP\TOTAL_TIME", (microtime(true) - \APP\START_TIME) * 1000);
		}
	}

	/**
	 * Fills $_GET with containing parameters, including the action and
	 * controller for the Route
	 *
	 * @return void
	 */
	public function fillGET()
	{
		$_GET = array_merge($this->_options, $this->_params, $_GET);
	}

	/**
	 * Returns action for Route
	 *
	 * @return string Route's Action
	 */
	public function getAction()
	{
		return $this->_action;
	}

	/**
	 * Returns controller for Route
	 *
	 * @return string Route's Controller
	 */
	public function getController()
	{
		return $this->_controller;
	}

	/**
	 * Returns rendering format for Route
	 *
	 * @return string Route's Rendering Format
	 */
	public function getFormat()
	{
		return $this->_params['format'];
	}

	/**
	 * Returns namespace for Route
	 *
	 * Note:  This is unfinished (just returns the default NS for now)
	 *
	 * @return string Route's Namespace
	 */
	public function getNamespace()
	{
		return isset($this->_options['namespace']) ? $this->_options['namespace'].'\\' : '';
	}

	/**
	 * Returns method for Route
	 *
	 * @return string Route's Method
	 */
	public function getMethod()
	{
		return $this->_method;
	}

	/**
	 * Returns controller class for Route
	 *
	 * @return string Route's Controller Class
	 */
	public function getControllerClass()
	{
		return ucfirst($this->getNamespace().\str::toCamelCase($this->_controller, true)).'Controller';
	}

	/**
	 * Returns view path for Route
	 *
	 * @return string Route's View Path
	 */
	public function getViewPath($action = null)
	{
		$action = is_null($action) ? $this->_action : $action;
		return str_replace('\\', '/', $this->getNamespace()).$this->_controller.'/'.$action;
	}

	/**
	 * Determines if passed $path matches.  Also validates method if passed
	 *
	 * @param string $path Path to match
	 * @param string $method HTTP Method
	 *
	 * @return P3\Routing\Route Returns self if matches, false otherwise.
	 */
	public function match($path, $method = null)
	{
		//printf("Checking Route: [%s] %s#%s (%s)<br />", $this->_method, $this->_controller, $this->_action, $this->_path);

		$path = rtrim($path, '/');
		$method = is_null($method) ? strtolower($_SERVER['REQUEST_METHOD']) : $method;

		/* Validate Method */
		if(!$this->_validateMethod($method)) return false;

		/* Handle Default route */
		if($this->_path == '')
			return $path == '' ? $this : false;

		/* Match Tokens */
		if($this->_matchTokens($path)) return $this;

		return false;
	}


	/**
	 * Opposite of match().  Matches controller/action/method instead of path
	 *
	 * @param string $controller Controller to match
	 * @param string $action Action to match
	 * @param string $method Method to match
	 *
	 * @return P3\Routing\Route Returns self if matches, false otherwise
	 */
	public function reverseMatch($controller, $action, $method)
	{
		//printf("Checking Route: [%s] %s#%s (%s)<br />", $this->_method, $this->_controller, $this->_action, $this->_path);
		return
			($this->_controller == $controller
				&& $this->_action == $action
				&& ($method == 'any' || $this->_method == $method)) ? $this : false;
	}


//- Protected
	/**
	 * Determines if the passed path(string) matches its own tokens
	 *
	 * @param string $path Path to attempt to match
	 *
	 * @return boolean Returns true if tokens match, false otherwise
	 */
	protected function _matchTokens($path)
	{
		/* Prepare self tokens */
		$self        = $this->_tokens;
		$self_tokens = $self[2];
		$self_seps   = $self[1];
		$self_len    = count($self_tokens);

		/* Prepare passed tokens */
		$passed        = $this->_tokenize($path);
		$passed_tokens = $passed[2];
		$passed_seps   = $passed[1];
		$passed_len    = count($passed_tokens);

		if($passed_len > $self_len)
			return false;


		/* Loop through tokens and check'm out */
		for($x = 0; $x < $self_len; $x++) {
			$self_token   = $self_tokens[$x];
			$passed_token = isset($passed_tokens[$x]) ? $passed_tokens[$x] : false;

			$self_sep   = $self_seps[$x];
			$passed_sep = isset($passed_seps[$x]) ? $passed_seps[$x] : null;

			//var_dump("Self T: ({$self_sep}){$self_token},   Passed T:({$passed_sep}){$passed_token}");

			/* If we are missing the token, and it's not optional - then we're done here */
			if($self_sep[0] == '[') {
				if(!is_null($passed_sep) && $passed_sep !== substr($self_sep, 1))
					return false;
			} else {
				if(!$passed_token)
					return false;
				if($passed_sep !== $self_sep)
					return false;
			}

			/* If the tokens aren't an exact match, lets investigate */
			if($self_token != $passed_token) {

				/* If it's not a bindable parameter, it's a bad route */
				if(!preg_match('/^:([^\/]*)/', $self_token, $m)) {
					return false;
				} else {
					/* Otherwise, let's bind the Param */

					if($m[0] == ':id')
						$passed_token = (int)$passed_token;

					$this->_params[$m[1]] = $passed_token;
				}
			}
		}

		/* Determine Controller */
		if(isset($this->_params['controller'])) $this->_controller = $this->_params['controller'];

		/* Determine Action */
		if(isset($this->_params['action'])) $this->_action = $this->_params['action'];

		/* Determine Format */
		$this->_params['format'] = isset($this->_params['format']) && $this->_params['format'] ? $this->_params['format'] : 'html';

		/* My Tokens match */
		return true;
	}

	/**
	 * Attempts to tokenize passed string (for use with _matchTokens)
	 *
	 * @param string $str String to tokenize
	 *
	 * @return array Returns token array from preg_match_all if successfull, otherwise returns false
	 */
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

	/**
	 * Validates whether or not Route's HTTP Method matches passed $method
	 *
	 * @param string $method Method to validate
	 *
	 * @return return boolean
	 */
	protected function _validateMethod($method)
	{
		return $this->_method == $method || $this->_method == 'any';
	}

//- Magic
	/**
	 * This is how netsted routing is achieved.  Any non existing method called
	 * on self will be forwarded to it's map.
	 *
	 * @param string $func
	 * @param array $args
	 *
	 * @return mixed
	 *
	 * @todo Add error handling to __call()
	 */
	public function __call($func, $args)
	{
		$options = isset($args[1]) ? $args[1] : array();
		$options['prefix'] = isset($options['prefix']) ? $options['prefix'] : $this(':'.\str::singularize($this->_controller).'_id');
		$options['prefix'] = rtrim($options['prefix'], '/').'/';


		return $this->_map->{$func}($args[0], $options);
	}

	/**
	 * This is how URLs are generated for forms/links.  Calling the route as
	 * a method with the appropriate model ids will return a usable URI
	 * for the link or form
	 *
	 * @param array $args
	 * @return string
	 */
	public function __invoke($ids, $options = array())
	{
		$ret = $this->_path;

		if(is_array($ids)) {
			foreach($ids as $k => $v)
				$ret = str_replace(':'.$k, $v, $ret);
		} else {
			$ret = str_replace(':id', $ids, $ret);
		}

		$ret = preg_replace('/\[.:format\]$/', '', $ret);
		return $ret;
	}
}
?>
