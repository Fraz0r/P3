<?php

namespace P3\ActionController\Exception;


/**
 * Description of multiple_render
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class MultipleRender extends \P3\Exception\ActionControllerException
{
	public function __construct($controller, $action, $format)
	{
		parent::__construct('%s supplied multiple responses for: %s (%s)', array($controller, $action, $format), 500);
	}
}

?>