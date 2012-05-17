<?php

namespace P3\ActionController;
use P3\Template\Layout;

/**
 * Description of action_view
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class ActionView extends \P3\Template\Base
{
	protected $_controller;
	protected $_layout;

	public function __construct($controller)
	{
		$this->_controller = $controller;
	}

	public function init_layout($path)
	{
		$this->set_layout(new Layout($path));
	}

	public function set_layout(Layout $layout)
	{
		$this->_layout = $layout;
	}
}

?>