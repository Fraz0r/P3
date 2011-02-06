<?php
/**
 * Description of MVC
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */

namespace P3\ActionController;

class Base extends \P3\Controller\Base
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
	 * Array of attributes
	 * 
	 * @var array
	 */
	protected $_attributes = array();

	/**
	 * True, if in an AJAX call
	 *
	 * @var boolean
	 */
	protected $_isXHR;

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
	protected $_rendered;

	/**
	 * Template to use for rendering
	 *
	 * @var \P3\Template\Base
	 */
	protected $_view;

	/**
	 * Constructor
	 * 
	 * @param array $routing_data
	 * @param array $options 
	 */
	public function  __construct($routing_data = null, array $options = array())
	{

		/* Create a uri, if null was passed */
		if(!count($routing_data)) {
			$routing_data = Router::parseRoute();
		}

		/* Save passed options */
		foreach ($options as $k => $v) {
			$this->setattribute($k, $v);
		}

		/* Define a new instance of our template class */
		if (isset($this->_attributes[self::ATTR_TEMPLATE_CLASS])) {
			$c = $this->getAttribute(self::ATTR_TEMPLATE_CLASS);
			$this->_view = new $c($routing_data);
		} else {
			$this->_view = new \P3\Template\Base($routing_data);
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

		/* Call parent constructor (to run the method) */
		parent::__construct($routing_data);

		/* If the method didnt render, and didnt return false... Auto-Render */
		if(!$this->_rendered && (is_null($this->_actionReturn) || (bool)$this->_actionReturn)) {
			$this->_display();
		}

	}

	/**
	 * Renders a page
	 *
	 * @param string $page
	 */
	public function _display($page = null) {
		$this->_view->display($page);
		$this->_rendered = true;
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
	public function setattribute($attr, $value)
	{
		$this->_attributes[$attr] = $value;
	}
}

?>