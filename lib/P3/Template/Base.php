<?php

namespace P3\Template;

/**
 * Template
 *
 * Template class used to render views
 */
class Base
{
//- Attributes
	const ATTR_CONTENT_TYPE           = 1;
	const ATTR_DOWNLOAD_AS_ATTACHMENT = 2;

	const CONTENT_TYPE_HTML      = 'text/html; charset=utf-8';
	const CONTENT_TYPE_PLAINTEXT = 'text/plain';

//- attr-protected
	/**
	 * Holds set attributes
	 * @var array
	 */
	protected $_attributes;

	/**
	 * Layout to wrap view in
	 * @var string
	 */
	protected $_layout;

	/**
	 * Last dirname from render
	 * @var string
	 */
	protected $_lastBase;

	/**
	 * Template page to render
	 * @var string
	 */
	protected $_page;

	/**
	 * Path to template
	 * @var string
	 */
	protected $_path;

	/**
	 * Route dispatched
	 */
	protected $_route;

	/**
	 * Vars being passed to view
	 * @var array
	 */
	protected $_vars = array();

//- Public
	/**
	 * Constructor
	 */
	public function __construct ($route = null, $path = null, array $options = array())
	{
		if(is_null($route)) {
			$router = \P3::getRouter();
			$route = \P3\Router::getDispatched();
		}

		$this->_route = $route;

		if(empty($path)) {
			$path = \P3\APP_PATH.'/views';
		}

		$this->_path = rtrim($path, '/');

		if(!empty($options)) {
			foreach( $options as $attr => $val ) {
				$this->setAttribute($attr, $val);
			}
		}
	}

	/**
	 * Assigns one or many variables to the view
	 *
	 * @param string,array $var
	 * @param any $val
	 * @return array
	 */
	public function assign($var, $val = null)
	{
		if(!is_array($var)) {
			return($this->_vars[$var] = $val);
		} else {
			foreach($var as $k => $v) {
				$this->_vars[$k] = $v;
			}
			return $this->_vars;
		}
	}

	/**
	 * Calls render, and echos the return
	 *
	 * @param string $path Page to render.  Null if using URI
	 */
	public function display($path = null)
	{
		if(is_null($path))
			$path = $this->_route->getViewPath();

		$display =  $this->render($path);

		if(isset($this->_attributes[self::ATTR_DOWNLOAD_AS_ATTACHMENT])) {
			header('Content-Disposition: attachment; filename="'.$this->getAttribute(self::ATTR_DOWNLOAD_AS_ATTACHMENT).'"');
			header('Content-Length: '.((int)strlen($display)));
		}

		if(isset($this->_attributes[self::ATTR_CONTENT_TYPE])) {
			$content_type = $this->getAttribute(self::ATTR_CONTENT_TYPE);
			header("Content-type: {$content_type}");
		}

		echo $display;
	}

	/**
	 * Magic Get:  Used to access assigned view vars
	 *
	 * @param string $name
	 * @return any
	 */
	public function  __get($name)
	{
		return(!empty($this->_vars[$name]) ? $this->_vars[$name] : false);
	}

	/**
	 * Returns rendered template
	 *
	 * @param string $path Page to render
	 * @return string
	 */
	public function render($path, array $vars = array())
	{
		$partial = false;
		$parts   = explode('/', $path);

		if($parts[count($parts)-1][0] == '_') {
			$partial = true;
		}

		if(count($parts) == 1) {
			$path = $this->_lastBase.'/'.$path;
		}

		if($path[0] == '\\') {
			$path = substr($path, 1);
		} else {
			if(!is_null($this->_route)) {
				$path = str_replace('\\', '/', $this->_route->getNamespace()).$path;
			}
		}


		$this->_lastBase = dirname($path);
		$file = $this->_path.'/'.$path.'.tpl';


		if(!is_readable($file))
			throw new \P3\Exception\ViewException('Template "%s" is not readable', array($file));

		extract(array_merge($this->_vars, $vars));
		try{
			ob_start();
			include($file);
			$content = ob_get_clean();

			if($partial || is_null($this->_layout)) {
				$rendered = $content;

				/* Change this one day */
				if($partial) echo $content;
			} else {
				ob_start();
				include(\P3\APP_PATH.'/layouts/'.$this->_layout);
				$rendered = ob_get_clean();
			}

		} catch(Exception $e) {
			var_dump($e);
		}

		return($rendered);
	}

	/**
	 * Returns set attribute
	 *
	 * @param int $attr
	 * @return any
	 */
	public function getAttribute($attr)
	{
		return $this->_attributes[$attr];
	}

	/**
	 * Magic Set: Assignes a view variable (Same as self->assign)
	 *
	 * @param string $name
	 * @param any $value
	 * @return bool
	 */
	public function __set($name, $value)
	{
		return($this->_vars[$name] = $value);
	}

	/**
	 *
	 * @param int Constant Attribute to assign
	 * @param any $val Value to assign
	 * @return bool
	 */
	public function setAttribute($attr, $val)
	{
		return $this->_attributes[$attr] = $val;
	}

	/**
	 * Sets the layout for rendering
	 *
	 * @param string $layout Layout to wrap views in
	 */
	public function setLayout($layout)
	{
		$this->_layout = $layout;
	}
}

?>
