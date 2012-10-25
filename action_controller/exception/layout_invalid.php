<?php

namespace P3\ActionController\Exception;

/**
 * Description of layout_invalid
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class LayoutInvalid extends \P3\Exception\ActionControllerException
{
	public function __construct($layout, $reason)
	{
		parent::__construct('Invalid layout \'%s\': %s', [$layout, $reason]);
	}
}

?>
