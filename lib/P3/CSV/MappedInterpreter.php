<?php
/**
 * Description of MappedInterpreter
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */
class P3_CSV_MappedInterpreter extends P3_CSV_Interpreter
{
	const ATTR_MAPPING          = 1;

	const MAPPING_SINGLE_HEADER = 101;

	protected $_attrs = array();

	public function __construct($path, array $options = null, $delimeter = null, $enclosure = null, $escape = null)
	{
		if(!empty($options)) {
			foreach($options as $attr => $val) {
				$this->setAttribute($attr, $val);
			}
		}

		parent::__construct($path, $delimeter, $enclosure, $escape);
	}

	public function getAttribute($attribute)
	{
		if(empty($this->_attrs[$attribute])) {
			return(null);
		}

		switch($attribute) {
			default:
				break;
		}
	}

	public function read()
	{
		$row = parent::read();

		if(!isset($this->_header)) {
			$this->_header = $row;
			$row = parent::read();
		}

		return(!$row ? false : array_combine($this->_header, $row));
	}

	public function setAttribute($attribute, $value)
	{
		switch($attribute) {
			default:
				$this->_attrs[$attribute] = $value;
				break;
		}
	}

}

?>
