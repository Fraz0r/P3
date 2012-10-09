<?php

namespace P3\Controller\Exception;

/**
 * Description of no_action
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoAction extends \P3\Exception\ControllerException 
{
	public function __construct($controller, $action)
	{
		return parent::__construct('%s doesn\'t have action: %s', array(
			get_class($controller),
			$action
		));
	}
}

?>