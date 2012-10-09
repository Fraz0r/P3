<?php

namespace P3\Object;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base 
{
//- Public
	public function send($what)
	{
		$args = func_get_args();
		$what = array_shift($args);
		
		return $what[0] == ':' ? call_user_func_array(array($this, substr($what, 1)), $args) : $this->{$what};
	}
}

?>