<?php
/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\ActiveRecord\Collection;
class Base extends \ArrayObject
{
	public function __construct($input)
	{
		parent::__construct($input, \ArrayObject::STD_PROP_LIST, '\P3\ActiveRecord\Collection\Iterator');
	}
}
?>