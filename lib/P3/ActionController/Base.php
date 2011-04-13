<?php
/**
 * Description of ActionController
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */

namespace P3\ActionController;

use P3\Router;

abstract class Base extends \P3\Controller\Base
{
//- ATTRIBUTES
	const ATTR_TEMPLATE_CLASS = 1;

//- attr-protected
	/**
	 * An Array of CSS Files for the layout to include
	 *
	 * @var array
	 */
	protected $_styles = array();

	/**
	 * An Array of JS Files for the laout to include
	 *
	 * @var array
	 */
	protected $_scripts = array();

	/**
	 * Layout to render
	 *
	 * @var string
	 */
	protected $_layout;

	/**
	 * Whether or not controller has been rendered
	 *
	 * @var boolean
	 */
	protected $_rendered = false;

	/**
	 * Whether or not controller has been processed
	 *
	 * @var boolean
	 */
	protected $_processed = false;

	/**
	 * Template to use for rendering
	 *
	 * @var \P3\Template\Base
	 */
	protected $_view = null;

//- Public
	/**
	 * Constructor
	 *
	 * @param P3\Routing\Route $route Dispatched Route (this needs to be gone)
	 * @param array $option Options for controller
	 */
	public function  __construct($route = null, array $options = array())
	{
		/* Save passed options */
		foreach ($options as $k => $v) {
			$this->setAttribute($k, $v);
		}

		$this->_prepareView();

		parent::__construct($route);
	}

	/**
	 * Process action and returns result
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	public function process($action = null)
	{
		if(!$this->_processed) {
			$action = is_null($action) ? $this->_route->getAction() : $action;
			$this->_actionReturn = $this->{$action}();

			if($this->_actionReturn !== FALSE && !$this->rendered())
				$this->render();
		}

		return $this->_actionReturn;
	}


	/**
	 * Renders controller#action view, or other disired view
	 *
	 * @param string $path path to view to render
	 *
	 * @return void
	 */
	public function render($path = null)
	{
		$this->_view->display($path);
		$this->_rendered = true;
	}

	/**
	 * Determines whether or not controller has been rendered
	 *
	 * @return boolean
	 */
	public function rendered()
	{
		return $this->_rendered;
	}

//- Protected
	/**
	 * Prepares controller's view
	 *
	 * @return P3\Template\Base
	 */
	protected function _prepareView()
	{
		/* Define a new instance of our template class */
		if (isset($this->_attributes[self::ATTR_TEMPLATE_CLASS])) {
			$c = $this->getAttribute(self::ATTR_TEMPLATE_CLASS);
			$this->_view = new $c($this->_route);
		} else {
			$this->_view = new \P3\Template\Base($this->_route);
		}

		/* Set our layout */
		if(!empty($this->_layout) && $this->_layout != 'none') {
			$this->_view->setLayout($this->_layout);
		}

		/* Add scripts to html helper */
		foreach($this->_scripts as $k => $v) {
			if($k !== strval($k)) {
				\html::addJs($v);
			} else {
				if(!isset($v['only']) || $routing_data['action'] == $v['only'] || (is_array($v['only']) && in_array($routing_data['action'], $v['only'])))
					\html::addJs($k);
			}
		}

		/* Add styles to html helper */
		foreach($this->_styles as $k => $v) {
			if($k !== strval($k)) {
				\html::addCss($v);
			} else {
				if(!isset($v['only']) || $routing_data['action'] == $v['only'] || (is_array($v['only']) && in_array($routing_data['action'], $v['only'])))
					\html::addCss($k);
			}
		}

		return $this->_view;
	}

//- Magic
	/**
	 * Called by php if function is missing.  Put this here to avoid the necessity
	 * of the action having to be in the controller
	 *
	 * THIS WILL BE GONE, I dont like it
	 *
	 * @param string $func
	 * @param array $args
	 */
	//public function __call($func, $args) {
	//}

	public function __set($var, $val)
	{
		$this->_view->$var = $val;
	}

	public function __get($var)
	{
		return $this->_view->$var;
	}

}

?>