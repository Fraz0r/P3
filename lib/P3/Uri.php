<?php

/**
 * P3_Uri
 *
 * Used to handle and create MVC Uri's
 */
class P3_Uri
{
	/**
	 * Stores each part of the URI in an Assoc. Array
	 *
	 * @var array
	 */
	protected $_requestUri;

	/**
	 * The controller of the URI
	 *
	 * @var string
	 */
	protected $_controller;

	/**
	 * The action of the URI
	 *
	 * @var string
	 */
	protected $_action;

	/**
	 * Array of arguments
	 *
	 * @var array
	 */
	protected $_args = array();

	/**
	 * Constructor
	 *
	 * @param string $uri
	 */
	public function __construct($uri = null){
		if($uri == null && !P3_Loader::isCli()){
			$uri = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		}

		if(!P3_Loader::isCli()) {
			$this->parse($uri);
		} else {
			$this->parseCli();
		}
	}

	/**
	 * Returns action based on URI
	 *
	 * @return string
	 */
	public function getAction()
	{
		if(empty($this->_action))
			$this->interpretSelf();

		return($this->_action);
	}


	/**
	 * Returns arguments as array
	 *
	 * @return array
	 */
	public function getArgs()
	{
		if(empty($this->_args))
			$this->interpretSelf();

		return($this->_args);
	}

	/**
	 * Alias of getArgs()
	 *
	 * @return array
	 */
	public function getArguments()
	{
		return $this->getArgs();
	}

	/**
	 * Returns the URI for a <base> tag (HTML)
	 *
	 * @return string
	 */
	public function getBase()
	{
		return 'http://'.$this->getHost().'/'.P3_PATH_PREFIX;
	}

	/**
	 * Returns controller based on URI
	 *
	 * @return string
	 */
	public function getController()
	{
		if(empty($this->_controller))
			$this->interpretSelf();

		return($this->_controller);
	}

	/**
	 * Returns host based on URI
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->_requestUri['host'];
	}

	/**
	 * Returns path based on URI
	 *
	 * @return string
	 */
	public function getPath($ltrim = false)
	{
		return(($ltrim) ? 
			ltrim($this->_requestUri['path'], '/') : 
			$this->_requestUri['path']);
	}

	/**
	 * Returns port based on URI
	 *
	 * @return string
	 */
	public function getPort()
	{
		return $this->_requestUri['port'];
	}

	/**
	 * Parses out controller, action, and args
	 *
	 * return void
	 */
	public function interpretSelf()
	{
		$path = $this->getPath(true);
		$ex   = explode('/', $path, 3);
		
		$this->_controller = (isset($ex[0]) && strlen($ex[0])) ? $ex[0] : 'default';
		$this->_args       = (isset($ex[2])) ? explode('/', $ex[2]) : array();
		$this->_action     = (isset($ex[1]) && strlen($ex[1])) ? $ex[1] : 'index';

		if((int)$this->_action > 0) {
			array_unshift($this->_args, (int)$this->_action);
			$this->_action = 'view';
		}

		if(count($_POST) && isset($_POST['xhr'])) {
			$this->_action = 'xhr';
		}
	}

	/**
	 * Parses a string to array using parse_url, sets the array internally
	 *
	 * @param string $uri
	 */
	private function parse($uri){
		$this->_requestUri = parse_url( $uri );

		/* Check if localhost (parse_url freaks on this for somereason (just the host) */
		if($this->_requestUri['host'] == '::1') {
			$this->_requestUri['host'] = 'localhost';
		}
		
		/* Handle Path Prefixing */
		//$this->_requestUri['path'] = str_replace(P3_PATH_PREFIX, '', $this->_requestUri['path'], 1);
		$this->_requestUri['path'] = preg_replace('~^/'.P3_PATH_PREFIX.'~', '/', $this->_requestUri['path']);
	}

	/**
	 * Parses uri when in Cli Mode
	 */
	private function parseCli()
	{
		$argv              = $_SERVER['argv'];
		$this->_controller = (!empty($argv[1])) ? $argv[1] : 'default';

		$args = array();
		if(!empty($argv[2])) {

			if(strtolower($argv[2]) == 'runall') {
				$this->_action  = 'index';
				$args[0] = 'runall';
			} else {
				$this->_action = $argv[2];
			}
		} else {
			$this->_action = 'index';
		}

		if(($count = count($argv)) > 3) {
			for($i = 3; $i < $count; $i++) {
				$args[] = $argv[$i];
			}
		}

		$this->_args = $args;
	}

}

?>
