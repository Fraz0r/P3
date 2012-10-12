<?php

namespace P3\Config\Handler;

/**
 * Description of ini
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Ini
{
	private $_file;
	private $_path;
	private $_data = [];
	private $_parse_sections;

	private static $_instances = [];

	public function __construct($path, $parse_sections = false)
	{
		$this->_file = basename($path);
		$this->_path = \P3\ROOT.'/config/'.$path;
		$this->_parse_sections = $parse_sections;

		$this->_data = parse_ini_file($this->_path, $parse_sections);
	}

	public function get_section($section)
	{
		if(!isset($this->_data[$section]))
			throw new \P3\Exception\ArgumentException\Invalid(get_class(), 'get_section', 'Section \''.$section.'\' not found in file \''.$this->_file.'\'');

		return $this->_data[$section];
	}

//- Static
	public static function read($path, $parse_sections = false)
	{
		if(!isset(self::$_instances[$path]))
			self::$_instances[$path] = new self($path, $parse_sections);

		return self::$_instances[$path];
	}
}

?>