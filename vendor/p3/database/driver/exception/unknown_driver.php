<?php

namespace P3\Database\Driver\Exception;

/**
 * Description of no_driver
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class UnknownDriver extends \P3\Exception\DatabaseException
{
	public function __construct($driver)
	{
		parent::__construct('Unknown driver \'%s\' specified in config/database.ini for %s environment', array($driver, \P3::env()), 500);
	}
}

?>