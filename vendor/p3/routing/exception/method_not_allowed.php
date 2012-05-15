<?php

namespace P3\Routing\Exception;

/**
 * Description of method_not_allowed
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class MethodNotAllowed extends \P3\Exception\RoutingException
{ 
	public function __construct($route, $method)
	{
		return parent::__construct('Method \'%s\' not allowed for %s#%s', array(
			$method, 
			$route->controller(), 
			$route->action()
		));
	}
}

?>