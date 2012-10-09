<?php

namespace P3\Exception;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \Exception
{
	public function __construct($format, array $args = array(), $code = 500)
	{
		parent::__construct(vsprintf($format, $args), $code);
	}
}

?>
