<?php

/**
 * P3_Controller_Abstract
 *
 * Base Class for P3's Controllers
 */
abstract class P3_Controller_Abstract
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
		if($routing_data == null) {
			$routing_data = P3_Router::parseRoute();
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
			throw new P3_Exception('Method not found in controller', 404);

		/* Run the action, and store the output */
		$this->_actionReturn = $this->{$this->_action}();

	}

//Static
	/**
	 * Renders the contents of a passed URI
	 *
	 * @param P3_Uri $uri URI to render
	 * @param string/null $layout_override If left out, the default layout for the controller will be used in the rendering.  If null, no layout will be used.  If string, will attempt to use passed layout
	 * @return string HTML Render for passed Uri
	 */
	public static function render(P3_Uri $uri, $layout_override = false)
	{
		return $ret;
	}

//Protected
	/* Overideable to fill vars prior to a "page" running */
	protected function _init()
	{
	}
}

?>