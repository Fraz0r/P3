<?php

/**
 * Description of Helper
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3;

class Helper {
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