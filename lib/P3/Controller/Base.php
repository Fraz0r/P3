<?php

/**
 * P3_Controller_Abstract
 *
 * Base Class for P3's Controllers
 */

namespace P3\Controller;

abstract class Base
{
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
	 * Routing Data Array
	 *
	 * @var array
	 */
	protected $_routing_data;

	/**
	 * Map (To name arguments)
	 *
	 * @var array
	 */
	protected $_argMap;

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
	public function __construct($routing_data = array())
	{
		/* If $uri is null, use current Uri (By creating one) */
		if(!count($routing_data)) {
			$routing_data = parseRoute();
		}

		/* Store vars (for controller use) */
		$this->_routing_data = $routing_data;
		$this->_action = $routing_data['action'];
		$this->_args   = $routing_data['args'];

		/* If arguments are mapped, set up access by named keys */
		if(!empty($this->_argMap) && isset($this->_argMap[$this->_action])) {
			foreach($this->_argMap[$this->_action] as $k => $v) {
				$this->_args[$v] = $this->_args[$k];
			}
		}

		/* Call init */
		$this->_init();

		/* Throw a 404 Error if the "page" wasn't found */
		if(!method_exists($this, $this->_action))
			throw new \P3\Exception\ControllerException(sprintf('Method "%s" not found in controller "%s"', $this->_action, $routing_data['controller']), 404);

		/* Run the action, and store the output */
		$this->_actionReturn = $this->{$this->_action}();

	}

//Static

//Protected
	/* Overideable to fill vars prior to a "page" running */
	protected function _init()
	{
	}
}

?>