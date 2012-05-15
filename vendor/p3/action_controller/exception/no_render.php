<?php

namespace P3\ActionController\Exception;

/**
 * Description of no_render
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoRender extends \P3\Exception\ActionControllerException
{
	public function __construct($controller)
	{
		parent::__construct('%s didn\'t supply output', array($controller), 500);
	}
}

?>