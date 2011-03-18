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
	/* Attributes */
	const ATTR_TEMPLATE_CLASS = 1;

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
	 * True upon rendering a view
	 *
	 * @var boolean
	 */
	protected $_rendered = false;

	protected $_processed = false;

	/**
	 * Template to use for rendering
	 *
	 * @var \P3\Template\Base
	 */
	protected $_view = null;

	/**
	 * Constructor
	 * 
	 * @param array $routing_data
	 * @param array $options 
	 */
	public function  __construct($route = null, array $options = array())
	{
		parent::__construct($route);

		/* Save passed options */
		foreach ($options as $k => $v) {
			$this->setAttribute($k, $v);
		}

		$this->_prepareView();

	}

	public function process($action = null)
	{
		if(!$this->_processed) {
			$action = is_null($action) ? $this->_route->getAction() : $action;
			$this->_actionReturn = $this->{$action}();
		}

		return $this->_actionReturn;
	}


	public function render($path = null)
	{
		$this->_view->display($path);
		$this->_rendered = true;
		if(defined('\APP\START_TIME')) {
			define('APP\RENDER_TIME', microtime(true));
		}
	}

	public function rendered()
	{
		return $this->_rendered;
	}

//protected
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
	}

//Magic
	/**
	 * Called by php if function is missing.  Put this here to avoid the necessity
	 * of the action having to be in the controller
	 *
	 * @param string $func
	 * @param array $args
	 */
	public function __call($func, $args) {
	}
}

?>