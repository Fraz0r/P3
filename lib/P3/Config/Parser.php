<?php

namespace P3\Config;

class Parser
{
	protected $_config = array();

	public function __construct(array $options = array())
	{
	}

	/**
	 * Returns section from config file
	 *
	 * @param <type> $section
	 * @return array Assoc array of config => value
	 */
	public function getSection($section)
	{
		return($this->_config[$section]);
	}

	/**
	 * Retrieves a specific var from config
	 *
	 * @param string $section Section to retrieve variable from
	 * @param string $var Variable to retrieve
	 * @return mixed Value
	 */
	public function getValue($section, $var)
	{
		if(isset($this->_config[$section][$var]))
			return($this->_config[$section][$var]);
		else
			return(NULL);
	}

	/**
	 * Loads Parser
	 *
	 * @param array $options
	 * @return P3\Config\Parser
	 */
	static public function load(array $options = array())
	{
		return(new self($options));
	}

	/**
	 * Reads file, or files, into config array
	 *
	 * @param string,array $files Array of filenames to read from
	 * @return array Config array
	 */
	public function read($files)
	{
		$files = is_array($files) ? $files : array($files);
		$ret = array();
		foreach($files as $file){
			list($bool, $config) = $this->readFile($file);
			$ret[$file] = $bool;

			if(!$bool) {
				throw new \P3\Exception\IOException('Unable to parse "%s" into the config', array($file));
			} else {
				$this->_config = array_merge($config, $this->_config);
			}
		}

		return($ret);
	}

	/**
	 * Sets variable in config
	 *
	 * @param string $section Section of variable to set
	 * @param string $var Variable to set
	 * @param mixed $val Value to set
	 */
	public function setValue($section, $var, $val) {
		$this->_config[$section][$var] = $val;
	}

	/**
	 * Reads file into config
	 *
	 * @param string $file File to read
	 * @return array 0 => boolean true if successfull, false otherwise. 1 => config array
	 */
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
