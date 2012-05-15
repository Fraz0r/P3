<?php

namespace P3\Exception\ArgumentException;

/**
 * Description of mismatch
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Mismatch extends \P3\Exception\ArgumentException
{
	public function __construct($class, $method, $num_expected, $num_passed)
	{
		parent::__construct(
			$class, 
			$method, 
			vsprintf('Expected %d arguments, but received %d', array(
				$num_expected, 
				$num_passed
			))
		);
	}
}

?>