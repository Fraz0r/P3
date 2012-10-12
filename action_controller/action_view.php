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

		$path = '';

		if(!is_null(($namespace = $this->_controller->route()->get_namespace())))
			$path .= '/'.str_replace('\\', '/', $namespace);

		$path .= '/'.$request->controller.'/'.$template.'.tpl';


		parent::__construct(\P3::config()->action_view->base_path.$path);
	}
}

?>