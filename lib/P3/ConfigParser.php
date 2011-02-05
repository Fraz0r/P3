<?php

namespace P3;

class ConfigParser
{
	protected $_config = array();

	public function __construct(array $options = array())
	{
	}

	public function getSection($section)
	{
		return($this->_config[$section]);
	}

	public function getValue($section, $var)
	{
		if(isset($this->_config[$section][$var]))
			return($this->_config[$section][$var]);
		else
			return(NULL);
	}

	/**
	 *
	 * @param array $options
	 * @return P3_ConfigParser
	 */
	static public function load(array $options = array())
	{
		return(new self($options));
	}

	public function read(array $files)
	{
		$ret = array();
		foreach($files as $file){
			list($bool, $config) = $this->readFile($file);
			$ret[$file] = $bool;

			if(!$bool) {
				throw new P3_Exception('Unable to parse "%s" into the config', array($file));
			} else {
				$this->_config = array_merge($config, $this->_config);
			}
		}

		return($ret);
	}

	public function setValue($section, $var, $val) {
		$this->_config[$section][$var] = $val;
	}

	protected function readFile($file)
	{
		if(file_exists($file)) {
			$config = parse_ini_file($file, true);
			$ret    = true;
		} else {
			$config = array();
			$ret    = false;
		}
		return(array($ret, $config));
	}
}

?>
