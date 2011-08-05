<?php

namespace P3\System\Logging;

/**
 * Description of Formatter
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\System\Logging
 * @version $Id$
 */
class Formatter 
{
	const FORMAT = '%s %s: %s';

	public function __invoke($severity, $timestamp, $progname, $msg)
	{
		return vsprintf(self::FORMAT, array(date('c', $timestamp), $progname, $msg));
	}
}

?>