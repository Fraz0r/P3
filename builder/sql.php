<?php

namespace P3\Builder;

/**
 * Description of mysql
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Sql
{
	/**
	 * Modes - Determine how to handle values that already exist for clause
	 */
	const MODE_APPEND   = 1;
	const MODE_PREPEND  = 2;
	const MODE_OVERRIDE = 3;

	/**
	 * Query Types
	 */
	const TYPE_DELETE = 1;
	const TYPE_INSERT = 2;
	const TYPE_SELECT = 3;
	const TYPE_UPDATE = 4;
	const TYPE_UNION  = 5;

	/**
	 * Join types
	 */
	const JOINTYPE_INNER  = 1;
	const JOINTYPE_LOUTER = 2;
	const JOINTYPE_ROUTER = 3;

	/**
	 * Type of query being built
	 * 
	 * @var int 
	 */
	private $_type = null;


	/**
	 * Class to fetch_into (on pdo)
	 * 
	 * @var string
	 */
	private $_fetch_class = null;

	/**
	 * Container for SQL clauses
	 * 
	 * @var array
	 */
	private $_sections  = array();

	/**
	 * Database table being built on
	 * 
	 * @var string
	 */
	private $_table     = null;

//- Public
	/**
	 * Intantiates new Query Builder
	 * 
	 * @param mixed $table_or_model Can be (string) table, or (ActiveRecord) model
	 * @param type $fetch_class fetch class for PDO
	 */
	public function __construct($table_or_model = null, $fetch_class = null)
	{
		if(!is_null($table_or_model)) {
			if(is_string($table_or_model)) {
				$this->_table = $table_or_model;
			} else {
				$this->_table = $table_or_model::get_table();
				$this->_fetch_class = get_class($table_or_model);
			}
		}

		if(!is_null($fetch_class))
			$this->_fetch_class = $fetch_class;

	}

	/**
	 * Clears any entry for given section
	 * 
	 * @param string $section section to clear
	 * @return void
	 */
	public function clear_section($section)
	{
		unset($this->_sections[$section]);

		return $this;
	}

	/**
	 * Starts a count query
	 * 
	 * @return P3\Database\Query\Builder 
	 */
	public function count($fields = '*')
	{
		$this->_sections = array('base' => 'SELECT COUNT('.$fields.') FROM '.$this->_table);

		$this->_set_type(self::TYPE_SELECT);
		return $this;
	}

	/**
	 * Starts a DELETE query
	 * 
	 * @return Builder 
	 */
	public function delete()
	{
		$this->_sections = array('base' => 'DELETE FROM '.$this->_table);

		$this->_set_type(self::TYPE_DELETE);
		return $this;
	}

	/**
	 * Execute query based on whats in _sections
	 * 
	 * @return mixed 
	 */
	public function execute()
	{
		$query = $this->__toString();
		return \P3::getDatabase()->exec($query);
	}

	/**
	 * Calls and returns fetchAll on PDOStatement
	 * 
	 * @param int $fetch_mode fetch mode to pass to PDOStatement
	 * @return array return from PDOSTatement
	 */
	public function fetch_all($fetch_mode = null)
	{
		$db    = \P3::getDatabase();
		$stmnt = $db->query($this->__toString());

		if(is_null($fetch_mode)) {
			if(!is_null($this->_fetch_class))
				$stmnt->setFetchMode(\PDO::FETCH_CLASS, $this->_fetch_class);
			else
				$stmnt->setFetchMode(\PDO::FETCH_ASSOC);
		} else {
			$stmnt->setFetchMode($fetch_mode);
		}

		return $stmnt->fetchAll();
	}

	public function fetch_class($class = null)
	{
		return is_null($class) ? $this->get_fetch_class() : $this->set_fetch_class($class);
	}

	public function fetch_column()
	{
		return $this->send()->fetchColumn();
	}

	public function fetch_count()
	{
		return $this->get_count_query()->fetch_column();
	}

	public function get_count_query()
	{
		if(isset($this->_count_query))
			return $this->_count_query;

		if(!in_array($this->get_type(), array(self::TYPE_SELECT, self::TYPE_UNION)))
			throw new \P3\Exception\QueryBuilderException("Can only convert select querys to a count");

		if($this->is_union() || $this->has_section('offset') || $this->has_section('limit') || $this->has_section('group')) {
			$builder = new self;

			$ret = $builder->select('COUNT(*)')->select_from($this);
		} else {
			$builder = clone $this;

			$ret = $builder->select('COUNT(*)', self::MODE_OVERRIDE)->remove_section('order');
		}

		return $ret;
	}

	/**
	 * Returns fetch class being used by PDOStatement
	 * 
	 * @return string class being fetched into
	 */
	public function get_fetch_class()
	{
		return $this->_fetch_class;
	}

	/**
	 * Returns query type (See constants at top of file)
	 * 
	 * @return string query string
	 */
	public function get_type()
	{
		return $this->_type;
	}

	/**
	 * Sets GROUP BY clause.  (override by default)
	 * 
	 * @param string,array $fields Fields to group
	 * @param type $mode append mode (MODE_OVERRIDE by default)
	 * @return Builder  self
	 */
	public function group($fields, $mode = self::MODE_OVERRIDE)
	{
		$fields = is_array($fields) ? implode(', ', $fields) : $fields;

		$this->_section('group', $fields, $mode);
		return $this;
	}

	public function has_section($section)
	{
		return 
			isset($this->_sections[$section])
				&& !empty($this->_sections[$section]);
	}

	/**
	 * Sets HAVING  clause.  (Append by default)
	 * 
	 * @param string,array $having
	 * @param type $mode append mode (MODE_APPEND by default)
	 * @return Builder 
	 */
	public function having($having, $mode = self::MODE_APPEND)
	{
		$having= is_array($having) ? implode(', ', $having) : $having;
		$this->_section('having', $having, $mode);
		return $this;
	}

	public function inner_join($table, $on)
	{
		return $this->join($table, $on, self::JOINTYPE_INNER);
	}

	/**
	 * Starts an INSERT query
	 * 
	 * @param array $fields fields to insert
	 * @return Builder 
	 */
	public function insert(array $fields)
	{
		$this->_sections = array('base' => "INSERT INTO ".$this->_table.'('.implode(', ', array_keys($fields)).')');
		$this->values(implode(', ', $fields));

		$this->_set_type(self::TYPE_INSERT);
		return $this;
	}

	public function is_delete()
	{
		return $this->get_type() == self::TYPE_DELETE;
	}

	public function is_insert()
	{
		return $this->get_type() == self::TYPE_INSERT;
	}

	public function is_select()
	{
		return $this->get_type() == self::TYPE_SELECT;
	}

	public function is_update()
	{
		return $this->get_type() == self::TYPE_UPDATE;
	}

	public function is_union()
	{
		return $this->get_type() == self::TYPE_UNION;
	}

	/**
	 * Joins a table to the query
	 * 
	 * @param string $table table to join
	 * @param string $on clause for `ON` statement
	 * @param int $join_type join type (JOINTYPE_INNER by default)
	 * @param int $mode append mode (MODE_APPEND by default)
	 * @return Builder self
	 */
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

	public function left_join($table, $on)
	{
		return $this->join($table, $on, self::JOINTYPE_LOUTER);
	}

	/**
	 * Sets LIMIT clause, with optional $offset
	 * 
	 * @param type $limit number of records to limit to
	 * @param type $offset records to offset (if any)
	 */
	public function limit($limit, $offset = null)
	{
		$this->_section('limit', $limit);

		if(!is_null($offset))
			$this->offset($offset);

		return $this;
	}

	/**
	 * Sets `OFFSET` in query
	 * 
	 * @param int $offset number of records to offset
	 */
	public function offset($offset)
	{
		$this->_section('offset', $offset);

		return $this;
	}

	/**
	 * Sets `ORDER BY` clause
	 * 
	 * @param string,array $order fields to sort by
	 */
	public function order($order, $mode = self::MODE_APPEND) 
	{
		$this->_section('order', $order, $mode);

		return $this;
	}

	public function right_join($table, $on)
	{
		return $this->join($table, $on, self::JOINTYPE_ROUTER);
	}

	/**
	 * Removes set section from query
	 * 
	 * @param string $section section to remove
	 */
	public function remove_section($section)
	{
		if($section == 'all') {
			$this->_sections = array();
		} elseif(is_array($section)) {
			foreach($section as $s) 
				unset($this->_sections[$s]);
		} else {
			unset($this->_sections[$section]);
		}

		return $this;
	}

	public function section_count($section)
	{
		if(!isset($this->_sections[$section])) {
			return 0;
		} elseif(is_array($this->_sections[$section])) {
			return count($this->_sections[$section]);
		} else {
			return 1;
		}
	}

	/**
	 * Starts a SELECT query
	 * 
	 * @param string,array $fields fields to select
	 * @return Builder self
	 */
	public function select($fields = '*', $mode = self::MODE_APPEND)
	{
		$fields = is_array($fields) ? implode(', ', $fields) : $fields;

		if($mode == self::MODE_APPEND) {
			if(isset($this->_sections['base']))
				$this->_sections['base'] .= ', '.$fields;
			else
				$this->_sections['base'] = 'SELECT '.$fields;
		} else {
			$this->_sections['base'] = 'SELECT '.$fields;
		}

		$this->_set_type(self::TYPE_SELECT);
		$this->select_from($this->_table);

		return $this;
	}

	/**
	 * Sets `FROM` class
	 * 
	 * @param string $from table to select from
	 * @return Builder self
	 */
	public function select_from($from)
	{
		if(is_object($from))
			$from = '('.$from->__toString().') as t';

		$this->_section('from', $from);
		return $this;
	}

	public function send()
	{
		return \P3::database()->query((string)$this);
	}

	/**
	 * Sets up `SET` clause
	 * 
	 * @param array $fields fields to set
	 * @param int $mode append mode (MODE_APPEND by default)
	 */
	public function set(array $fields, $mode = self::MODE_APPEND)
	{
		$set = array();
		foreach($fields as $k => $v)
			if($v !== 'NULL' && !is_numeric($v))
				$v = '\''.$v.'\'';

			$set[] = $k.'='.$v;

		$this->_section('set', $set, $mode);
	}

	public function set_count_query($query)
	{
		if(is_a($query, 'P3\Database\Query\Builder'))
			$query = $query->__toString();

		$this->_count_query = $query;
	}

	/**
	 * Set class to fetch into
	 * 
	 * @param string $class class to fetch records into
	 */
	public function set_fetch_class($class)
	{
		$this->_fetch_class = $class;
	}

	/**
	 * Gets table name (if table is null) 
	 * - OR -
	 * Sets name, and optionally, alias for table
	 * 
	 * @param string $table table name
	 * @param string $alias table alias
	 * @return mixed void or string 
	 */
	public function table($table = null)
	{
		if(!is_null($table)) {
			$this->_table = $table;
		} else {
			return $this->_table;
		}
	}

	/**
	 * Converts this query to union of one or more other builders
	 * 
	 * 	NOTE:  You lose access to indivual secions of these builders, you can, however,
	 * 			add limits and orders on the new builder as you would any others.
	 * 
	 * @param array,Builder $builder Builder or array builders to unionize
	 * 
	 * @return Builder $this 
	 */
	public function union($builders)
	{
		if(!is_array($builders))
			$builders = array($builders);

		$this->_sections = array('base' => self::unionize(array_merge(array($this), $builders)));
		$this->_set_type(self::TYPE_UNION);

		return $this;
	}

	/**
	 * Starts UPDATE query
	 * 
	 * @param array,string $fields Fields to update
	 * @return Builder  self
	 */
	public function update($fields)
	{
		$this->_sections = array('base' => 'UPDATE '.$this->_table);

		$this->set($fields, self::MODE_OVERRIDE);
		$this->_set_type(self::TYPE_UPDATE);
		return $this;
	}

	/**
	 * Sets up VALUES clause
	 * 
	 * @param string,array $values values for clause
	 * @param int $mode apend mode (default MODE_OVERRIDE)
	 * @return Builder 
	 */
	public function values($values, $mode = self::MODE_OVERRIDE)
	{
		$values = is_array($values) ? implode(', ', $values) : $values;
		$this->_section('values', $values, $mode);
		return $this;
	}

	/**
	 * Sets up WHERE clause
	 * 
	 * @param array,string $conditions Conditions for WHERE clause
	 * @param int $mode append mode (Default MODE_OVERRIDE)
	 * @return Builder self
	 */
	public function where($conditions, $mode = self::MODE_APPEND)
	{
		if(is_array($conditions)) {
			if(is_numeric(key($conditions))) {
				$format = array_shift($conditions);
				$clause = vsprintf($format, array_map(function($v){ return \P3::database()->quote($v); }, $conditions));
			} else {
				$parts = [];

				foreach($conditions as $k => $v)
					$parts[] = $k.' = '.\P3::database()->quote($v);

				$clause = implode(' AND ', $parts);
			}
		} else {
			$clause = $conditions;
		}

		$this->_section('where', $clause, $mode);
		return $this;
	}


//- Private

	/**
	 * Returns clause [section]
	 * 
	 * @param string $section section to get
	 * @return string section value
	 */
	private function _get_section($section)
	{
		if(!isset($this->_sections[$section])) 
			return '';

		$prepend_space = true;
		$ret = false;

		$val = $this->_sections[$section];
		switch ($section) {
			case 'from':
				$ret .= 'FROM '.$val;
				break;
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
					if(isset($this->_sections['offset']))
						$ret .= $this->_get_section('offset');
				break;
			case 'offset':
				$ret .= 'OFFSET '.$val;
				break;
			case 'order':
				$ret .= 'ORDER BY '.(is_string($val) ? $val : implode(', ', $val));
				break;
			case 'update':
				break;
			case 'set':
				$ret .= 'SET '.(is_array($val) ? implode(', ', $val) : $val);
				break;
			case 'values':
				$ret .= 'VALUES('.(is_array($val) ? implode(', ') : $val).')';
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
		}

		if(!$ret) {
			return '';
		} else {
			if($prepend_space)
				$ret = ' '.$ret;

			return $ret;
		}
	}

	/**
	 * Sets section, or appends/prepends to it (based on $mode)
	 * 
	 * @param string $section Section to setup
	 * @param mixed $val value for section
	 * @param int $mode append mode (MODE_OVERRIDE by default)
	 */
	private function _section($section, $val, $mode = self::MODE_OVERRIDE)
	{
		switch($mode) {
			case self::MODE_OVERRIDE:
				$this->_sections[$section] = $val;
				break;
			case self::MODE_PREPEND:
				if(!isset($this->_sections[$section])) {
					$this->_sections[$section] = $val;
				} elseif(is_array($this->_sections[$section])) {
					array_unshift($this->_sections[$section],  $val);
				} else {
					$tmp = $this->_sections[$section];
					$this->_sections[$section] = array($val, $tmp);
				}
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

	/**
	 * Sets type of query
	 * 
	 * @param int $type TYPE flag
	 */
	private function _set_type($type)
	{
		$this->_type = $type;
	}

//- Static
	/**
	 * Takes an array of builders, and returns a UNION'ized query string
	 * 
	 * @param array $builders array of builders
	 * @return string SQL query 
	 */
	public static function unionize(array $builders)
	{
		$q = array();

		foreach($builders as $b)
			$q[] = (string)$b;

		return '('.implode(') UNION (', $q).')';
	}

//- Magic
	/**
	 * Uses TYPE to join the propper clauses for the current query, and returns the 
	 * built query
	 * 
	 * @return type string rendered SQL Query from _sections
	 */
	private function __toString()
	{
		if(empty($this->_sections['base']))
				throw new \P3\Exception\QueryBuilderException("You asked me to build a query for which you have not started?");

		$query = $this->_sections['base'];

		switch ($this->_type) {
			case self::TYPE_DELETE:
				$query .= $this->_get_section('where');
				$query .= $this->_get_section('limit');
				break;
			case self::TYPE_INSERT:
				$query .= $this->_get_section('values');
				break;
			case self::TYPE_SELECT:
			case self::TYPE_UNION:
				$query .= $this->_get_section('from');
				$query .= $this->_get_section('joins');
				$query .= $this->_get_section('where');
				$query .= $this->_get_section('group');
				$query .= $this->_get_section('having');
				$query .= $this->_get_section('order');
				$query .= $this->_get_section('limit');
				break;
			case self::TYPE_UPDATE:
				$query .= $this->_get_section('joins');
				$query .= $this->_get_section('set');
				$query .= $this->_get_section('where');
				$query .= $this->_get_section('limit');
				break;
		}

		return $query;
	}
}

?>