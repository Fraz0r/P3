<?php

namespace P3\System;

/**
 * Logger for P3
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\System
 * @version $Id$
 */
class Logger extends Logging\Engine\Base
{
	public function __construct()
	{
		$env = \P3::getEnv();

		switch($env) {
			case 'production':
				$level = Logging\REPORTING_LEVEL_PROD;
				break;
			case 'development':
				$level = Logging\REPORTING_LEVEL_DEV;
				break;
			default:
				$level = Logging\REPORTING_LEVEL_NONE;
		}

		parent::__construct(\P3\ROOT.'/log/'.$env.'.log', $level, \P3::getAppName());
	}
}

?>