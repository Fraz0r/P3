<?php

namespace P3;

/**
 * Description of version
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Version 
{
	const MAJOR = 2;
	const MINOR = 0;
	const TINY  = 0;
	const PRE   = 'dev';

	public static function string()
	{
		return implode('.', [self::MAJOR, self::MINOR, self::TINY]).'-'.self::PRE;
	}
}

?>