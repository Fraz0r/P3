<?php

namespace P3\Controller;
use       P3\Router;

/**
 * Base Class for P3's Controllers.  Rarely needed - Use ActionController\Base for
 * your controllers unless you need only the very basic functionaly of a controller
 * 
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\Controller
 * @version $Id$
 */
abstract class Base
{
//- attr-protected
	/**
	 * Array of attributes
	 *
	 * @var array
	 */
	protected $_attributes = array();
	/**
	 * The method to call within the controller
	 *
	 * @var string
	 */
	protected $_action;

	/**
	 * Arguments for the controller
	 *
	 * @var array
	 */
	protected $_args = array();

	/**
	 * Route
	 *
	 * @var \P3\Routing\Route
	 */
	protected $_route;

	/**
	 * Holds whatever was returned by the action
	 *
	 * @var mixed
	 */
	protected $_actionReturn;

//- Public
	/**
	 * Constructor
	 *
	 * @param array $routing_data
	 */
	public function __construct($route = null)
	{
		$this->_route = $route;
	}

	/**
	 * Retrives an attribute
	 *
	 * @param int $attr
	 * @return mixed
	 */
	public function getAttribute($attr)
	{
		return(isset($this->_attributes[$attr])? $this->_attributes[$attr] : null);
	}

	/**
	 * Sets an attribute
	 *
	 * @param int $attr
	 * @param mixed $value
	 */
	public function setAttribute($attr, $value)
	{
		$this->_attributes[$attr] = $value;
	}

	/**
	 * Convinience method for P3\Router::redirect
	 * 
	 * @param string $path path to send to P3\Router::redirect
	 * @see P3\Router::redirect()
	 */
	public function redirect($path)
	{
		\P3\Router::redirect($path);
	}

//- Protected
	/**
	 * Overridable stub
	 * 
	 * Runs prior to action being processed.  Use this as a before_filter
	 * 
	 * @return void
	 */
	protected function _init()
	{
	}
}

?>