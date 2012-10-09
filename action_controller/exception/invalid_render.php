<?php

namespace P3\ActionController\Exception;

/**
 * Description of invalid_render
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class InvalidRender extends \P3\Exception\ActionControllerException
{
	public function __construct($format, array $vars = array())
	{
		parent::__construct($format, $vars);
	}
}

?>