<?php

namespace P3\Routing\Exception;
use       P3\Net\Http\Response;

/**
 * Description of no_route_matched
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoRouteMatched extends \P3\Exception\RoutingException
{ 
	public function __construct()
	{
		parent::__construct('No routes matched request: %s', array(\P3::request()->url()), Response::STATUS_NOT_FOUND);
	}
}

?>
