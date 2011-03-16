<?php

/**
 * P3_Controller_Abstract
 *
 * Base Class for P3's Controllers
 */

namespace P3\Controller;

use P3\Router;

abstract class Base
{

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
	 * @var <unkown>
	 */
	protected $_actionReturn;

	/**
	 * Array of models to load before running action
	 *
	 * @var array
	 */
	public $_models = array();

	/**
	 * Constructor
	 *
	 * @param array $routing_data
	 */
	public function __construct($route = null)
	{
		$route = is_null($route) ? Router::getRoute() : $route;

		$this->_route = $route;

		/* Call init */
		$this->_init();
	}

	/**
	 * Retrives an attribute
	 *
	 * @param int $attr
	 * @return <unknown>
	 */
	public function getAttribute($attr)
	{
		return(isset($this->_attributes[$attr])? $this->_attributes[$attr] : null);
	}

	/**
	 * Sets an attribute
	 *
	 * @param int $attr
	 * @param <unknown> $value
	 */
	public function setAttribute($attr, $value)
	{
		$this->_attributes[$attr] = $value;
	}

//Static

//Protected
	/* Overideable to fill vars prior to a "page" running */
	protected function _init()
	{
	}
}

?>