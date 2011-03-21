<?php

namespace P3\Helper;

/**
 * P3\Helper\Base
 *
 * Base class for helpers
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Base {
	protected $_opts = array();

	public function  __construct(array $options = array())
	{
		foreach($options as $opt => $val) $this->_opts[$opt] = $val;
	}

	protected function getOpt($opt, $default = null)
	{
		return isset($this->_opts[$opt]) ? $this->_opts[$opt] : $default;
	}

	protected function setOpt($opt, $val)
	{
		$this->_opts[$opt] = $val;
	}
}
?>