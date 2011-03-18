<?php

/**
 * Base Routing class for P3
 *
 * @author Tim <tim.frazier@gmail.com>
 */

namespace P3;

final class Router extends Routing\Engine\Base
{
	public static function add($route)
	{
		if(FALSE !== $route->match($_SERVER['REQUEST_URI']))
			$route->dispatch();

		parent::add($route);
	}
}
?>