<?php

namespace P3\ActionController;

/**
 * Description of format_responder
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class FormatResponder
{
	private $_controller;
	private $_formats;

	public function __construct($controller)
	{
		$this->_controller = $controller;
	}

	public function get_response($request = null)
	{
		if(is_null($request))
			$request = \P3::request;

		$format = $request->format();

		if(!isset($this->_formats[$format]))
			throw new Exception\NoResponse(array_merge($_GET, array('format' => $format)));

		$responder = $this->_formats[$format];

		if(!$responder) {
			$this->_controller->render_template($_GET['action']);
		} else {
			$this->_controller->set_response($responder());
		}
	}

	public function __call($method, array $args = array())
	{
		if(!($count = count($args))) {
			$this->_formats[$method] = false;
		} else if($count > 1) {
			throw new Exception\ResponderInvalid('Format responders only take 0 or 1 arguments', array(), 500);
		} else {
			$closure = $args[0];

			//TODO: Check if callable
			$this->_formats[$method] = $closure;
		}
	}
}

?>
