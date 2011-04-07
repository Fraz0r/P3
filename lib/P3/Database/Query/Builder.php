<?php

namespace P3\Database\Query;

/**
 * This class is responsible for building SQL queries throughout P3
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Builder 
{
	const MODE_APPEND   = 1;
	const MODE_OVERRIDE = 2;

	const TYPE_DELETE = 1;
	const TYPE_INSERT = 2;
	const TYPE_SELECT = 3;
	const TYPE_UPDATE = 4;

	const JOINTYPE_INNER  = 1;
	const JOINTYPE_LOUTER = 2;
	const JOINTYPE_ROUTER = 3;

	private $_queryType = null;
	private $_sections  = array();
	private $_table     = null;

//- Public
	public function __construct($table_or_model)
	{
		$this->_table = is_string($table_or_model) ? $table_or_model : $table_or_model::table();
	}

	public function delete()
	{
		$this->_sections['base'] = 'DELETE FROM '.$this->_table;

		$this->_setQueryType(self::TYPE_DELETE);
		return $this;
	}

	public function execute()
	{
		$query = $this->_buildQuery();
		var_dump($query); die;
	}

	public function group()
	{
		return $this;
	}

	public function having()
	{
		return $this;
	}

	public function insert()
	{
		$this->_setQueryType(self::TYPE_INSERT);
		return $this;
	}

	public function join($table, $on, $join_type = self::JOINTYPE_INNER)
	{
		switch($join_type) {
		}

		$this->_section('joins', $val, self::MODE_APPEND);
		return $this;
	}

	public function select($fields = '*')
	{
		$fields = is_array($fields) ? implode(', ', $fields) : $fields;

		$this->_section('base', 'SELECT '.$fields.' FROM '.$this->_table, self::MODE_OVERRIDE);

		$this->_setQueryType(self::TYPE_SELECT);
		return $this;
	}

	public function set()
	{
	}

	public function update()
	{
		$this->_sections['base'] = 'UPDATE '.$this->_table;

		$this->_setQueryType(self::TYPE_UPDATE);
		return $this;
	}

	public function values()
	{
		return $this;
	}

	public function where($conditions, $mode = self::MODE_OVERRIDE)
	{
		if(is_array($conditions)) {
			/* Todo:  create this functionality */
		} else {
			$clause = $conditions;
		}

		$this->_section('where', $clause, $mode);
		return $this;
	}


//- Private
	private function _buildQuery()
	{
		$query = $this->_sections['base'];

		switch ($this->_queryType) {
			case self::TYPE_DELETE:
				$query .= $this->_getSection('joins');
				$query .= $this->_getSection('where');
				break;
			case self::TYPE_INSERT:
				break;
			case self::TYPE_SELECT:
				$query .= $this->_getSection('joins');
				$query .= $this->_getSection('where');
				break;
			case self::TYPE_UPDATE:
				$query .= $this->_getSection('joins');
				break;
		}

		return $query;
	}

	private function _getSection($section)
	{
		$ret = '';

		if(!isset($this->_sections[$section])) 
			return $ret;

		$val = $this->_sections[$section];
		$ret .= ' ';
		switch ($section) {
			case 'delete':
				break;
			case 'group':
				break;
			case 'having':
				break;
			case 'insert':
				break;
			case 'join':
				break;
			case 'select':
				break;
			case 'update':
				break;
			case 'where':
				$ret .= 'WHERE ';
				if(is_string($val)) {
					$ret .= $val;
				} else {
					array_walk($val, function(&$v, $k){ $v = '('.$v.')'; });
					$ret .= implode(' AND ', $val);
				}
				break;
			case 'values':
				break;
		}

		return $ret;
	}

	private function _section($section, $val, $mode)
	{
		switch($mode) {
			case self::MODE_OVERRIDE:
				$this->_sections[$section] = $val;
				break;
			case self::MODE_APPEND:
				if(is_array($this->_sections[$section])) {
					$this->_sections[$section][] = $val;
				} else {
					$tmp = $this->_sections[$section];
					$this->_sections[$section] = array($tmp, $val);
				}
				break;
		}
	}

	private function _setQueryType($type)
	{
		$this->_queryType = $type;
	}
}

?>
