<?php

namespace P3\Database\Driver\Mysql;

/**
 * Description of DateTime
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class DateTime extends \DateTime
{
	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}

	public function __invoke($format)
	{
		var_dump("HIT");
		return $this->format($format);
	}
}

?>