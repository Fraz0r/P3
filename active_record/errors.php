<?php

namespace P3\ActiveRecord;

/**
 * Description of errors
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Errors implements \ArrayAccess, \Countable
{
	protected $_data = ['base' => []];
	protected $_model;

	public function __construct($model)
	{
		$this->_model = $model;
	}

	public function add($field, $error)
	{
		if(!isset($this->_data))
			$this->_data[$field] = [];

		$this->_data[$field][] = $error;
	}

	public function add_base($error)
	{
		return $this->add('base', $error);
	}

	public function count()
	{
		$count = 0;

		foreach($this->_data as $k => $v)
			$count += count($v);

		return $count;
	}

	public function full_messages()
	{
		$messages = [];

		foreach($this->_data as $field => $errors) {
			foreach($errors as $error) {
				if($field == 'base') {
					$messages[] = $error;
				} else {
					$field = \str::humanize($field, true);

					$messages[] = $field.' '.$error;
				}
			}
		}

		return new \P3\Object\Collection($messages);
	}

	public function offsetExists($offset)
	{
		return isset($this->_data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->_data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		return $this->_data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
	}
}

?>