<?php

namespace P3\ActionController\Exception;

/**
 * Description of no_render
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoRender extends \P3\Exception\ActionControllerException
{
	public function __construct($controller, $action, $format)
	{
		parent::__construct('%s didn\'t supply output for action: %s (%s)', array($controller, $action, $format), 500);
	}
}

?>