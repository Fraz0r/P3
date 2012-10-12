<?php

namespace P3\System\Logging\Formatter;

/**
 * Description of default
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class DefaultFormatter
{
	const FORMAT = '%s, %s %s: %s';

	public function __invoke($severity, $timestamp, $progname, $msg)
	{
		return vsprintf(self::FORMAT, array($severity[0], date('c', $timestamp), $progname, $msg));
	}
}
?>