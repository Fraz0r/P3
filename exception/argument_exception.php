<?php

namespace P3\Exception;

/**
 * Description of argument_exception
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class ArgumentException extends Base 
{
	public function __construct($class, $method, $why)
	{
		parent::__construct('%s#%s: %s', array($class, $method, $why));
	}
}

?>