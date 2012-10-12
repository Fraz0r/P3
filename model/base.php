<?php

namespace P3\Model;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \P3\Object\Base
{
	protected $_data = array();

	public function __construct(array $data = [])
	{
		$this->_data = $data;
	}

//- Protected
	protected function _read_attribute($attribute)
	{
		if(!isset($this->_data[$attribute]))
			throw new Exception\AttributeNoExist(get_called_class(), $attribute);

		return $this->_data[$attribute];
	}

	protected function _write_attribute($attribute, $value)
	{
		if(!isset($this->_data[$attribute]))
			throw new Exception\AttributeNoExist(get_called_class(), $attribute);

		$this->_data[$attribute] = $value;

		return $value;
	}
}

?>