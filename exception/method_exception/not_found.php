<?php

namespace P3\Exception\MethodException;

/**
 * Description of method_not_found
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class MethodNotFound extends \P3\Exception\Base
{
	public function __construct($class, $method)
	{
		parent::__construct('%s does not implement method: %s', array($class, $method));
	}
}

?>