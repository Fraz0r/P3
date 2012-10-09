<?php

namespace P3\Routing\Exception;

/**
 * Description of method_not_allowed
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Invalid extends \P3\Exception\RoutingException
{ 
	public function __construct($route, $reason)
	{
		return parent::__construct($reason.': '.json_encode($route->options()));
	}
}

?>