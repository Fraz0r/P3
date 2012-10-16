<?php

namespace P3\Database\Table;

/**
 * Description of column
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * 
 * @todo I think the DateTime toString could be dangerous on diff database implementations.  Probably need the driver to control?  For now, fuck it
 */
class Column
{
	const FLAG_UNSIGNED = 1;
	const FLAG_NOT_NULL = 2;
	
	// TODO: ADD ALLLLLLL TYPES.  QUIT BEING LAZY.  ALSO MAKES SURE THEY ARE MAPPED INTO RECORD _read_attribute and _write_attribute
	const TYPE_BOOL      = 'bool';
	const TYPE_CHAR      = 'char';
	const TYPE_DATE      = 'date';
	const TYPE_DATETIME  = 'datetime';
	const TYPE_INT       = 'int';
	const TYPE_TEXT      = 'text';
	const TYPE_TIMESTAMP = 'timestamp';
	const TYPE_TINY_INT  = 'tinyint';
	const TYPE_VARCHAR   = 'varchar';

	protected $_name;
	protected $_type;
	protected $_flags;

	public function __construct($name, $type, $flags = 0)
	{
		$this->_name  = $name;
		$this->_type  = $type;
		$this->_flags |= $flags;
	}

	public function get_type()
	{
		return $this->_type;
	}

	public function parsed_read($val)
	{
		switch($this->_type)
		{
			case self::TYPE_BOOL:
				return (bool)$val;
			case self::TYPE_DATE:
			case self::TYPE_DATETIME:
			case self::TYPE_TIMESTAMP:
				$date_class = \P3::database()->get_date_time_class();
				return new $date_class($val);
			case self::TYPE_INT:
			case self::TYPE_TINY_INT:
				return (int)$val;
			case self::TYPE_CHAR:
			case self::TYPE_VARCHAR:
			case self::TYPE_TEXT:
			default:
				return $val;
		}
	}

	public function parsed_write($val)
	{
		switch($this->_type)
		{
			case self::TYPE_BOOL:
				return (int)$val;
			case self::TYPE_DATE:
			case self::TYPE_DATETIME:
			case self::TYPE_TIMESTAMP:
				if(is_object($val))
					$val = (string)$val;
				else
					$val = date('Y-m-d H:i:s', strtotime($val));

				return $val;
			case self::TYPE_INT:
			case self::TYPE_TINY_INT:
				return (int)$val;
			case self::TYPE_CHAR:
			case self::TYPE_VARCHAR:
			case self::TYPE_TEXT:
			default:
				return $val;
		}
	}
}

?>