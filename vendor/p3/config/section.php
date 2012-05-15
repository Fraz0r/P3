<?php

namespace P3\Config;

/**
 * Description of section
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Section
{
	protected $_data = array();
	protected $_name;

//- Public
	public function __construct($name, array $data = array())
	{
		$this->_data = $data;
		$this->_name = $name;
	}

	public function get($var)
	{
		if(!isset($this->_data[$var]))
			$this->_data[$var] = new self($var);

		return $this->_data[$var];
	}

	public function get_multiple(array $vars)
	{
		return array_intersect($this->_data, $vars);
	}

	public function set_multiple(array $vals)
	{
		foreach($vals as $k => $v)
			$this->set($k, $v);

		return $this;
	}

	public function get_vars()
	{
		$ret = array();

		foreach($this->_data as $k => $v)
			if(!is_a('P3\Config\Section'))
				$ret[$k] = $v;

		return $ret;
	}

	public function set($var, $val)
	{
		$this->_data[$var] = $val;

		return $this;
	}

//- Magic
	public function __get($var)
	{
		return $this->get($var);
	}

	public function __set($var, $val)
	{
		$this->set($var, $val);
	}
}

?>