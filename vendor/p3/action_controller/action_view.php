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
	protected $_template;

	public function __construct($controller, $template = null)
	{
		$request = $controller->get_request();

		if(!isset($template))
			$template = $request->action();

		$this->_controller = $controller;
		$this->_template   = $template;

		parent::__construct(\P3\ROOT.'/app/views/'.$request->controller.'/'.$template.'.tpl');
	}
}

?>