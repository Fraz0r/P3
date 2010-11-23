<?php

/**
 * EEF_Controller_Abstract
 *
 * Base Class for EEF's Controllers
 */
abstract class EEF_Controller_Abstract
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
	 * URI passed to controller
	 *
	 * @var EEF_Uri
	 */
	protected $_uri;

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
	 * @param EEF_Uri $uri
	 */
	public function __construct(EEF_Uri $uri = null)
	{
		/* If $uri is null, use current Uri (By creating one) */
		if($uri == null) {
			$uri = new EEF_Uri;
		}

		/* Store vars (for controller use) */
		$this->_uri    = $uri;
		$this->_action = $uri->getAction();
		$this->_args   = $uri->getArguments();

		/* If arguments are mapped, set up access by named keys */
		if(!empty($this->_argMap) && isset($this->_argMap[$this->_action])) {
			foreach($this->_argMap[$this->_action] as $k => $v) {
				$this->_args[$v] = $this->_args[$k];
			}
		}

		/* Call init */
		$this->init();

		/* Throw a 404 Error if the "page" wasn't found */
		if(!method_exists($this, $this->_action))
			throw new EEF_Exception('Method not found in controller', 404);

		/* Run the action, and store the output */
		$this->_actionReturn = $this->{$this->_action}();

	}

	/* Overideable to fill vars prior to a "page" running */
	protected function init()
	{
	}
}

?>