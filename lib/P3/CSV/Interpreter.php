<?php
/**
 * Description of Interpreter
 *
 * @author Tim Frazier <tim@essential-elements.net>
 */

class P3_CSV_Interpreter
{
	public    $_data = array();

	protected $_delimeter;
	protected $_enclosure;
	protected $_escape;
	protected $_fh;
	protected $_path;
	protected $_pointer;

	public function  __construct($path, $delimeter = null, $enclosure = null, $escape = null)
	{
		$this->_path      = $path;
		$this->_delimeter = !empty($delimeter) ? $delimeter : ',';
		$this->_enclosure = !empty($enclosure) ? $enclosure : '"';
		$this->_escape    = !empty($escape)    ? $escape    : '\\';
	}

	public function close()
	{
		return fclose($this->_fh);
	}

	public function open()
	{
		if(FALSE !== ($this->_fh = fopen($this->_path, 'r'))) {
			$this->_pointer = 0;
		} else {
			throw new P3_Exception('Cannot open CSV File "%s" for processing', array($path));
		}
	}

	public function read()
	{
		if(empty($this->_fh)) {
			$this->open();
		}

		if(FALSE === ($ret = fgetcsv($this->_fh, null, $this->_delimeter, $this->_enclosure, $this->_escape))) {
			fclose($this->_fh);
		}

		return $ret;
	}

	public function readAll()
	{
		$this->open();

		while(FALSE !== ($row = fgetcsv($this->_fh, null, $this->_delimeter, $this->_enclosure, $this->_escape))) {
			$this->_data[] = $row;
		}

		$this->close();

		return $this->_data;
	}
}

?>
