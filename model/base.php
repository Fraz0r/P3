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

	public function attr_exists($attr)
	{
		return array_key_exists($attr, $this->_data);
	}

//- Protected
	protected function _read_attribute($attribute)
	{
		if(!$this->attr_exists($attribute))
			throw new Exception\AttributeNoExist(get_called_class(), $attribute);

		return isset($this->_data[$attribute]) ? $this->_data[$attribute] : null;
	}

	protected function _write_attribute($attribute, $value)
	{
		if(!$this->attr_exists($attribute))
			throw new Exception\AttributeNoExist(get_called_class(), $attribute);

		$this->_data[$attribute] = $value;

		return $value;
	}

//- Magic
	public function __call($method, array $args = [])
	{
		if($this->attr_exists($method))
			return !count($args) ? $this->_read_attribute($method) : $this->_write_attribute($method, $args[0]);

		throw new \P3\Exception\MethodException\NotFound(get_called_class(), $method);
	}
	public function __get($var)
	{
		return call_user_func_array(array($this, $var), []);
	}

	public function __set($var, $val)
	{
		return call_user_func_array(array($this, $var), [$val]);
	}
}

?>