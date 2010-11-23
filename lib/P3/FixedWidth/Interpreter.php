<?php
/**
 * Description of Interpreter
 *
 * @author Tim Frazier <tim@essential-elements.net>
 */

class P3_FixedWidth_Interpreter
{
	protected $_cols;
	protected $_fh;
	protected $_data;
	protected $_open;
	protected $_path;
	protected $_pointer;

	public function  __construct($path, array $cols = null)
	{
		$this->_path = $path;
		if(!empty($cols)) {
			$this->map($cols);
		}
	}

	/**
	 * @todo Make this, gonna suck to guess widths but it'd be nice to have
	 */
	public function figureWidths($line)
	{
		$len = strlen($line);
	}

	public function map(array $cols)
	{
		$this->_cols = $cols;
	}

	public function mapLine($line) {
		$ret = array();

		$i = 0;
		foreach($this->_cols as $k => $length) {
			$ret[$k] = preg_replace('![\s]+!', ' ', rtrim(substr($line, $i, $length)));

		$i += $length;
		}

		return $ret;
	}

	public function read()
	{
		if(!$this->_open) {
			$this->open();
		}

		if(FALSE === ($ret = fgets($this->_fh))) {
			$this->close();
		} else {
			$ret = $this->mapLine($ret);
		}

		return $ret;
	}

	protected function close()
	{
		fclose($this->_fh);
		$this->_open = false;
	}

	protected function open()
	{
		if(FALSE !== ($this->_fh = fopen($this->_path, 'r'))) {
			$this->_pointer = 0;
			$this->_open    = true;
		} else {
			throw new P3_Exception('Failed to open FixedWidth File \'%s\'', array($this->_path));
		}
	}
}

?>