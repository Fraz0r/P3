<?php

namespace P3\Database\Query;

/**
 * This class is responsible for building SQL queries throughout P3
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Database\Query
 * @version $Id$
 */
class Builder 
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

	/**
	 * Join types
	 */
	const JOINTYPE_INNER  = 1;
	const JOINTYPE_LOUTER = 2;
	const JOINTYPE_ROUTER = 3;

	/**
	 * PDO Statement used in fetch()
	 *  
	 * @var PDOStatement 
	 * @see fetch
	 */
	private $_fetchStmnt = null;

	/**
	 * Pointer for fetch()
	 * 
	 * @var int
	 * @see fetch
	 */
	private $_fetchPointer = null;

	/**
	 * Flags set on Builder
	 * 
	 * @var int
	 */
	private $_flags     = 0;

	/**
	 * Type of query being built
	 * 
	 * @var int 
	 */
	private $_queryType = null;


	/**
	 * Class to fetch_into (on pdo)
	 * 
	 * @var string
	 */
	private $_intoClass = null;

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
	 * @param type $alias alias of table, if any
	 * @param type $intoClass fetch class for PDO
	 * @param type $flags flags to set on Builder
	 */
	public function __construct($table_or_model = null, $alias = null, $intoClass = null, $flags = 0)
	{
		$this->_flags = $flags;
		$this->_alias = $alias;


		if(!is_null($table_or_model)) {
			if(is_string($table_or_model)) {
				$this->_table = $table_or_model;
			} else {
				$this->_table = $table_or_model::table();
				$this->_intoClass = get_class($table_or_model);
			}
		}

		if(!is_null($alias))
			$this->alias($alias);

		if(!is_null($intoClass))
			$this->_intoClass = $intoClass;

	}

	/**
	 * Get or Set table alias
	 * 
	 * @param string $alias alias to set, current alias is returned if this is null
	 * @return mixed alias or null
	 */
	public function alias($alias = null)
	{
		if(!is_null($alias)) {
			$this->_alias = $alias;

			/* Will probably change this at some point */
			$this->_table = $this->_table.' '.$alias;
		} else {
			return $this->_alias;
		}
	}

	/**
	 * Starts a count query
	 * 
	 * @return P3\Database\Query\Builder 
	 */
	public function count()
	{
		$this->_sections = array('base' => 'SELECT COUNT(*) FROM '.$this->_table);

		$this->_setQueryType(self::TYPE_SELECT);
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

		$this->_setQueryType(self::TYPE_DELETE);
		return $this;
	}

	/**
	 * Execute query based on whats in _sections
	 * 
	 * @return mixed 
	 */
	public function execute()
	{
		$query = $this->_buildQuery();
		return \P3::getDatabase()->exec($query);
	}

	/**
	 * Returns fetch class being used by PDOStatement
	 * 
	 * @return string class being fetched into
	 */
	public function getFetchClass()
	{
		return $this->_intoClass;
	}

	/**
	 * Fetches next record on PDOStatement
	 * 
	 * @param int $fetchMode fetch mode pass to PDOStatement (if any)[:w
	 * @return mixed
	 */
	public function fetch($fetchMode = null)
	{
		if(is_null($this->_fetchStmnt)) {
			$db    = \P3::getDatabase();
			$this->_fetchStmnt = $db->query($this->getQuery());
			$this->_fetchPointer = 0;

			if(is_null($fetchMode)) {
				if(!is_null($this->_intoClass))
					$this->_fetchStmnt->setFetchMode(\PDO::FETCH_CLASS, $this->_intoClass);
				else
					$this->_fetchStmnt->setFetchMode(\PDO::FETCH_ASSOC);
			} else {
				$this->_fetchStmnt->setFetchMode($fetchMode);
			}
		}

		if(!$this->_fetchStmnt)
			return false;

		return $this->_fetchStmnt->fetch();

	}

	/**
	 * Calls and returns fetchAll on PDOStatement
	 * 
	 * @param int $fetchMode fetch mode to pass to PDOStatement
	 * @return array return from PDOSTatement
	 */
	public function fetchAll($fetchMode = null)
	{
		$db    = \P3::getDatabase();
		$stmnt = $db->query($this->getQuery());

		if(is_null($fetchMode)) {
			if(!is_null($this->_intoClass))
				$stmnt->setFetchMode(\PDO::FETCH_CLASS, $this->_intoClass);
			else
				$stmnt->setFetchMode(\PDO::FETCH_ASSOC);
		} else {
			$stmnt->setFetchMode($fetchMode);
		}

		return $stmnt->fetchAll();
	}

	/**
	 * Returns query as executable string
	 * 
	 * @return string query string
	 */
	public function getQuery()
	{
		return $this->_buildQuery();
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

		$this->_setQueryType(self::TYPE_INSERT);
		return $this;
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
	}

	/**
	 * Sets `OFFSET` in query
	 * 
	 * @param int $offset number of records to offset
	 */
	public function offset($offset)
	{
		$this->_section('offset', $offset);
	}

	/**
	 * Sets `ORDER BY` clause
	 * 
	 * @param string,array $order fields to sort by
	 */
	public function order($order) 
	{
		$this->_section('order', $order, self::MODE_OVERRIDE);
	}

	/**
	 * Removes set section from query
	 * 
	 * @param string $section section to remove
	 */
	public function removeSection($section)
	{
		if(is_array($section)) {
			foreach($section as $s) unset($this->_sections[$s]);
		} else {
			unset($this->_sections[$section]);
		}
	}

	public function sectionCount($section)
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
	public function select($fields = '*')
	{
		$fields = is_array($fields) ? implode(', ', $fields) : $fields;

		$this->_sections['base'] = 'SELECT '.$fields;

		$this->_setQueryType(self::TYPE_SELECT);
		$this->selectFrom($this->_table);

		return $this;
	}

	/**
	 * Sets `FROM` class
	 * 
	 * @param string $from table to select from
	 * @return Builder self
	 */
	public function selectFrom($from)
	{
		if(is_object($from))
			$from = '('.$from->getQuery().') as t';

		$this->_section('from', $from);
		return $this;
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

	/**
	 * Set class to fetch into
	 * 
	 * @param string $class class to fetch records into
	 */
	public function setFetchClass($class)
	{
		$this->_intoClass = $class;
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
	public function table($table = null, $alias = null)
	{
		if(!is_null($table)) {
			$this->_table = $table;

			if(!is_null($alias))
				$this->alias($alias);
		} else {
			return $this->_table;
		}
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
		$this->_setQueryType(self::TYPE_UPDATE);
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
	public function where($conditions, $mode = self::MODE_OVERRIDE)
	{
		if(is_array($conditions)) {
			$format = array_shift($conditions);
			$clause = sprintf($format, $conditions);
		} else {
			$clause = $conditions;
		}

		$this->_section('where', $clause, $mode);
		return $this;
	}


//- Private
	/**
	 * Uses TYPE to join the propper clauses for the current query, and returns the 
	 * built query
	 * 
	 * @return type string rendered SQL Query from _sections
	 */
	private function _buildQuery()
	{
		if(empty($this->_sections['base']))
				throw new \P3\Exception\QueryBuilderException("You asked me to build a query for which you have not started?");

		$query = $this->_sections['base'];

		switch ($this->_queryType) {
			case self::TYPE_DELETE:
				$query .= $this->_getSection('where');
				$query .= $this->_getSection('limit');
				break;
			case self::TYPE_INSERT:
				$query .= $this->_getSection('values');
				break;
			case self::TYPE_SELECT:
				$query .= $this->_getSection('from');
				$query .= $this->_getSection('joins');
				$query .= $this->_getSection('where');
				$query .= $this->_getSection('group');
				$query .= $this->_getSection('having');
				$query .= $this->_getSection('order');
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

	/**
	 * Returns clause [section]
	 * 
	 * @param string $section section to get
	 * @return string section value
	 */
	private function _getSection($section)
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
						$ret .= $this->_getSection('offset');
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
	private function _setQueryType($type)
	{
		$this->_queryType = $type;
	}
}

?>
