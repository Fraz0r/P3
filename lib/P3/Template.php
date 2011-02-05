<?php
/**
 * Template
 *
 * Template class used to render views
 */

namespace P3;

class Template
{
	/* Attributes */
	const ATTR_CONTENT_TYPE           = 1;
	const ATTR_DOWNLOAD_AS_ATTACHMENT = 2;

	const CONTENT_TYPE_HTML      = 'text/html; charset=utf-8';
	const CONTENT_TYPE_PLAINTEXT = 'text/plain';

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
	 * URI dispatched
	 * @var P3_Uri
	 */
	protected $_routing_data;

	/**
	 * Vars being passed to view
	 * @var array
	 */
	protected $_vars = array();

	/**
	 * Constructor
	 */
	public function __construct ($routing_data = null, $path = null, array $options = array())
	{
		$this->_routing_data = $routing_data;

		if(empty($path)) {
			$path = \P3\APP_PATH.'/views';
		}

		$this->_path = realpath($path);

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
	 * @param string $page Page to render.  Null if using URI
	 */
	public function display($page = null)
	{
		if(is_null($page))
			$page = $this->_routing_data['controller'].'/'.$this->_routing_data['action'].'.tpl';

		$display =  $this->render($page);

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
	 * @param string $page Page to render
	 * @return string
	 */
	public function render($page)
	{
		$file = $this->_path.'/'.$page;

		if(!is_readable($file))
			throw new Exception('Template "%s" is not readable', array($file));

		extract($this->_vars);
		try{
			ob_start();
			include($file);
			$content = ob_get_clean();

			if(is_null($this->_layout)) {
				$rendered = $content;
			} else {
				ob_start();
				include(APP_PATH.'/layouts/'.$this->_layout);
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
