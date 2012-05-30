<?php

namespace P3\Template;

/**
 * Template class used to render views
 * 
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Template
 * @version $Id$
 */
class Base
{
//- Attributes
	const ATTR_CONTENT_TYPE           = 1;
	const ATTR_DOWNLOAD_AS_ATTACHMENT = 2;

	const CONTENT_TYPE_HTML      = 'text/html; charset=utf-8';
	const CONTENT_TYPE_PLAINTEXT = 'text/plain';

	public static $MIME_RENDER_HEADERS = array(
		'csv'  => 'text/csv',
		'json' => 'application/json',
		'text' => 'text/plain',
		'javascript' => 'text/javascript'
	);

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
		}

		$this->_route = $route;

		if(empty($path)) {
			$path = \P3\APP_PATH.'/views';
	 	} else {
			$path = \P3\APP_PATH.'/views/'.$path;
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

	public function display($what, array $vars = array(), array $options = array())
	{
		echo $this->render($what, $vars, $options);
	}

	/**
	 * Magic Get:  Used to access assigned view vars
	 *
	 * @param string $name
	 * @return any
	 */
	public function  __get($name)
	{
		return(isset($this->_vars[$name]) ? $this->_vars[$name] : null);
	}

	public function render($what, array $vars = array(), array $options = array())
	{
		if(!is_array($what)) {
			return $this->renderPath($what, $vars);
		} else {
			$type    = key($what);	
			$content = current($what);

			if(!isset(static::$MIME_RENDER_HEADERS))
				throw new \P3\Exception\ViewException("P3 doesn't know how to render type '%s'.  If you feel it should, Id love to know about it.  Submit an issue on github. ;)", array($type), 500);

			$header = static::$MIME_RENDER_HEADERS[$type];

			if(isset($options['filename'])) {
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private",false);
				header("Content-Type: {$header}");
				header('Content-Disposition: attachment; filename="'.$options['filename'].'";');
				header('Content-Length: '.((int)strlen($content)));
				header("Content-Transfer-Encoding: binary");
			} else {
				header("Content-Type: {$header}");
			}

			echo $content;
		}
	}

	/**
	 * Returns rendered template
	 *
	 * @param string $path Page to render
	 * @return string
	 */
	public function renderPath($path, array $vars = array())
	{
		$partial = false;
		$parts   = explode('/', $path);

		if($parts[count($parts)-1][0] == '_')
			$partial = true;

		if(count($parts) == 1 && !is_null($this->_route)) 
			$path = $this->_route->getController().'/'.$path;

		$file = $this->viewPath($path);


		if(!is_readable($file))
			throw new \P3\Exception\ViewException('Template "%s" is not readable', array($file));


		extract(array_merge($this->_vars, $vars));
		try{
			if($partial) {
				if(isset($vars['collection'])) {
					foreach($vars['collection'] as $plural_var => $collection) {
						$content = '';
						foreach($collection as $collection_item) {
							if(!isset($singular))
								$singular = \str::singularize($plural_var);

							${$singular} = $collection_item;
							ob_start();
							include($file);
							$content .= ob_get_clean();
						}
						break; //only want/need one iteration (to grab k & v)
					}

					echo $content;
					return true;
				} else {
					include($file);
					return true;
				}

				return false;
			} else {
				ob_start();
				include($file);
				$content = ob_get_clean();

				if(!is_null($this->_layout)) {
					ob_start();
					include(\P3\APP_PATH.'/layouts/'.$this->_layout);
					$rendered = ob_get_clean();
				} else {
					$rendered = $content;
				}
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

	public function viewPath($path)
	{
		return $this->_path.'/'.$path.'.tpl';
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
