<?php

namespace P3\Template;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base
{
	protected $_file;
	protected $_path;
	protected $_vars = array();

	private static $_buffers = array();

	public function __construct($path)
	{
		$this->_path = dirname($path);
		$this->_file = basename($path);
	}

//- Public
	public function assign(array $vars)
	{
		foreach($vars as $k => $v)
			$this->__set($k, $v);
	}

	public function yield($buffer = 'view')
	{
		if(!isset(self::$_buffers[$buffer]))
			throw new Exception\UnknownBuffer($this->_file, $buffer);
	}

//- Magic
	public function __get($var)
	{
		if(!isset($this->_vars[$var]))
			throw new Exception\VarNoExist('%s was never set, but was attempted access', array($var));

		return $this->_vars[$var];
	}

	public function __set($var, $val)
	{
		$this->_vars[$var] = $val;
	}
}

?>