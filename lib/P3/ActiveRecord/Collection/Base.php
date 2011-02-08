<?php
/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\ActiveRecord\Collection;
class Base extends \ArrayIterator
{
	public function __construct(array $array = array())
	{
	 parent::__construct($array);
	}
}
?>