<?php

namespace P3\Exception\MethodException;


/**
 * Description of not_implemented
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NotImplemented extends \P3\Exception\MethodException
{
	public function __construct($method)
	{
		parent::__construct(
				is_array($method) ? '%s#%s not yet implemented' : '%s not yet implemented', 
				$method
		);
	}
}

?>