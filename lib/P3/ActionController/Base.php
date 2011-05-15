<?php

namespace P3\ActionController;
use       P3\Router;

/**
 * This is the class to extend your Base controllers from.  Typically, only one 
 * to few controllers should extend this class directly.  Your controllers should
 * extend eachother to help stay DRY.
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\ActionController
 * @version $Id$
 */
abstract class Base extends \P3\Controller\Base
{
//- ATTRIBUTES
	const ATTR_TEMPLATE_CLASS = 1;

//- attr-protected
	/**
	 * An Array of CSS Files for the layout to include
	 * 
	 * @see _prepareView
	 * @var array
	 */
	protected $_styles = array();

	/**
	 * An Array of JS Files for the laout to include
	 * 
	 * @see _prepareView
	 * @var array
	 */
	protected $_scripts = array();

	/**
	 * Layout to render
	 * 
	 * @see _prepareView
	 * @var string
	 */
	protected $_layout;

	/**
	 * Whether or not controller has been rendered
	 * 
	 * @see rendered
	 * @var boolean
	 */
	protected $_rendered = false;

	/**
	 * Whether or not controller has been processed
	 * 
	 * @see process
	 * @var boolean
	 */
	protected $_processed = false;

	/**
	 * Template to use for rendering
	 * 
	 * @see _prepareView
	 * @var \P3\Template\Base
	 */
	protected $_view = null;

//- Public
	/**
	 * You typically wont instantiate new controllers in your code.  This is used
	 * in P3 during the Dispatch Process
	 * 
	 * @see P3\Routing\Engine\Base::dispatch()
	 *
	 * @param P3\Routing\Route $route dispatched route (this needs to be gone)
	 * @param array $option options for controller
	 * @return void
	 */
	public function  __construct($route = null, array $options = array())
	{
		/* Save passed options */
		foreach ($options as $k => $v) {
			$this->setAttribute($k, $v);
		}

		parent::__construct($route);

		$this->_prepareView();
	}

	/**
	 * Dispatches controller, and renders action template (If action didn't return
	 * FALSE, and didn't render on it's own
	 * 
	 * @param string $action action to process and render
	 * @return void
	 */
	public function dispatch($action = null)
	{
		if($this->process($action) !== FALSE && !$this->rendered())
			$this->render();
	}

	/**
	 * Process action and returns result
	 *
	 * @param string $action action to proccess.  If action is null, it will be pulled from the route
	 * @return void
	 */
	public function process($action = null)
	{
		/* Call init */
		$this->_init();

		if(!$this->_processed) {
			$action = is_null($action) ? $this->_route->getAction() : $action;
			$this->_actionReturn = $this->{$action}();

			if(($this->_actionReturn === true || is_null($this->_actionReturn)) && !$this->rendered())
				$this->render();
		}

		return $this->_actionReturn;
	}


	/**
	 * Renders controller#action view, or other disired view
	 *
	 * @param string $path path to view to render
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

	/**
	 * Verifies whether or not the template exists 
	 * 
	 * @param type $path  Path controller wants to render
	 */
	public function templateExists($path)
	{
		return file_exists($this->_view->viewPath($this->_route->getViewPath($path)));
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
	 * Sets variable in view
	 * 
	 * @param string $var var to set
	 * @param string $val value to set
	 * @magic
	 */
	public function __set($var, $val)
	{
		$this->_view->$var = $val;
	}

	/**
	 * Retrieves variable from view
	 * 
	 * @param string $var var to get
	 * @return mixed var value
	 */
	public function __get($var)
	{
		return $this->_view->$var;
	}

}

?>