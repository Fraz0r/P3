<?php

namespace P3\Routing\Exception;

/**
 * Description of no_routes
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoRoutes extends \P3\Exception\RoutingException
{
	public function __construct()
	{
		parent::__construct('No routes found in config/routes.php');
	}
}

?>