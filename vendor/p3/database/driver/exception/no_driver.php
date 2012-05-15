<?php

namespace P3\Database\Driver\Exception;

/**
 * Description of no_driver
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoDriver extends \P3\Exception\DatabaseException
{
	public function __construct()
	{
		parent::__construct('No driver specified in config/database.ini for %s environment', array(\P3::env()), 500);
	}
}

?>