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
	public function __construct($table_or_model, $alias = null)
	{
		$this->_alias = $alias;
		$this->_table = (is_string($table_or_model) ? $table_or_model : $table_or_model::table()).(is_null($alias) ? '' : ' '.$alias);
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

	public function group($fields, $mode = self::MODE_OVERRIDE)
	{
		$fields = is_array($fields) ? implode(', ', $fields) : $fields;

		$this->_section('group', $fields, $mode);
		return $this;
	}

	public function having($having, $mode = self::MODE_APPEND)
	{
		$having= is_array($having) ? implode(', ', $having) : $having;
		$this->_section('having', $having, $mode);
		return $this;
	}

	public function insert(array $fields)
	{
		$this->_sections['base'] = "INSERT INTO ".$this->_table.'('.implode(', ', array_keys($fields)).')';
		$this->values(implode(', ', $fields));

		$this->_setQueryType(self::TYPE_INSERT);
		return $this;
	}

	public function join($table, $on, $join_type = self::JOINTYPE_INNER, $mode = self::MODE_APPEND)
	{
		switch($join_type) {
			case self::JOINTYPE_INNER:
				$val = 'INNER JOIN ';
				break;
			case self::JOINTYPE_LOUTER:
				$val = 'LEFT OUTER JOIN ';
				break;
			case self::JOINTYPE_ROUTER:
				$val = 'RIGHT OUTER JOIN ';
				break;
		}

		$val .= $table.' ON '.$on;

		$this->_section('joins', $val, $mode);
		return $this;
	}

	public function limit($limit, $offset = null)
	{
		$this->_section('limit', $limit);

		if(!is_null($offset))
			$this->offset($offset);
	}

	public function offset($offset)
	{
		$this->_section('offset', $offset);
	}

	public function select($fields = '*')
	{
		$fields = is_array($fields) ? implode(', ', $fields) : $fields;

		$this->_section('base', 'SELECT '.$fields.' FROM '.$this->_table);

		$this->_setQueryType(self::TYPE_SELECT);
		return $this;
	}

	public function set(array $fields, $mode = self::MODE_APPEND)
	{
		$set = array();
		foreach($fields as $k => $v)
			$set[] = $k.'=\''.$v.'\'';

		$this->_section('set', $set, $mode);
	}

	public function update($fields)
	{
		$this->_sections['base'] = 'UPDATE '.$this->_table;

		$this->set($fields, self::MODE_OVERRIDE);
		$this->_setQueryType(self::TYPE_UPDATE);
		return $this;
	}

	public function values($values, $mode = self::MODE_OVERRIDE)
	{
		$values = is_array($values) ? implode(', ', $values) : $values;
		$this->_section('values', $values, $mode);
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
				//$query .= $this->_getSection('joins');
				$query .= $this->_getSection('where');
				$query .= $this->_getSection('limit');
				break;
			case self::TYPE_INSERT:
				$query .= $this->_getSection('values');
				break;
			case self::TYPE_SELECT:
				$query .= $this->_getSection('joins');
				$query .= $this->_getSection('where');
				$query .= $this->_getSection('group');
				$query .= $this->_getSection('having');
				$query .= $this->_getSection('limit');
				break;
			case self::TYPE_UPDATE:
				$query .= $this->_getSection('joins');
				$query .= $this->_getSection('set');
				$query .= $this->_getSection('where');
				$query .= $this->_getSection('limit');
				break;
		}

		return $query;
	}

	private function _getSection($section)
	{
		if(!isset($this->_sections[$section])) 
			return '';

		$prepend_space = true;
		$ret = false;

		$val = $this->_sections[$section];
		switch ($section) {
			case 'group':
				$ret .= 'GROUP BY '.(is_string($val) ? $val : implode(', ', $val));
				break;
			case 'having':
				$ret .= 'HAVING '.(is_string($val) ? $val : implode(', ', $val));
				break;
			case 'joins':
				$ret .= is_string($val) ? $val : implode(' ', $val);
				break;
			case 'limit':
				$ret .= 'LIMIT '.$val;
				$ret .= $this->_getSection('offset');
				break;
			case 'offset':
				$ret .= ', '.$val;
				$prepend_space = false;
				break;
			case 'update':
				break;
			case 'set':
				$ret .= 'SET '.(is_array($val) ? implode(', ', $val) : $val);
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
				$ret .= 'VALUES('.(is_array($val) ? implode(', ') : $val).')';
				break;
		}

		if(!$ret) {
			return '';
		} else {
			if($prepend_space)
				$ret = ' '.$ret;

			return $ret;
		}
	}

	private function _section($section, $val, $mode = self::MODE_OVERRIDE)
	{
		switch($mode) {
			case self::MODE_OVERRIDE:
				$this->_sections[$section] = $val;
				break;
			case self::MODE_APPEND:
				if(!isset($this->_sections[$section])) {
					$this->_sections[$section] = $val;
				} elseif(is_array($this->_sections[$section])) {
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
