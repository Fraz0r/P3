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

		$path = implode('/', [static::get_controller_base($controller), $template]);

		parent::__construct(self::view_path($path));
	}

	public function init_layout($layout_base)
	{
		$format = $this->_controller->get_request()->format();
		$acceptable = ["{$layout_base}.{$format}.tpl", "{$layout_base}.tpl"];
		$flag = false;

		foreach($acceptable as $attempt)
			if(is_readable(self::base_path().'/layouts/'.$attempt)) {
				$flag = true;
				parent::init_layout($attempt);
				break;
			}

		if(!$flag)
			throw new Exception\LayoutInvalid($layout_base, 'Doesn\'t exist');
	}

//- Static
	public static function base_path()
	{
		return \P3::config()->action_view->base_path;
	}

	public static function is_readable($view_path)
	{
		return is_readable(self::view_path($view_path));
	}

	public static function view_path($endpoint)
	{
		return implode('/', [self::base_path(), $endpoint]).'.tpl';
	}

	public static function get_controller_base($controller)
	{
		// TODO:  This doesn't seem right..
		if($controller instanceof \P3\ActionMailer\Base)
			return '/'.\str::from_camel(get_class($controller));

		$path = '';

		if(!is_null(($namespace = $controller->route()->get_namespace())))
			$path .= '/'.str_replace('\\', '/', $namespace);

		$path .= '/'.$controller->get_request()->controller;

		return $path;
	}
}

?>