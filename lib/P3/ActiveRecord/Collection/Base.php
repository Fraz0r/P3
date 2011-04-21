<?php

namespace P3\ActiveRecord\Collection;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * P3\Active\Record\Collection\Base
 *
 * Container for ActiveRecords
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Base implements  \IteratorAggregate , \ArrayAccess , \Countable
{
//- attr-protected
	protected $_builder      = null;
	protected $_contentClass = null;
	protected $_data         = array();
	protected $_flags        = 0;
	protected $_parentModel  = null;
	protected $_state        = 0;
	protected $_countQuery   = null;
	protected $_statement    = null;
	
	private $_fetchPointer = -1;

//- Public
	/**
	 * Construct
	 *
	 * @param array $input Array of records
	 * @param P3\ActiveRecord\Base $parent Parent model, if any
	 */
	public function __construct(QueryBuilder $builder, $parentModel = null, $flags = 0)
	{
		$this->_builder     = $builder;
		$this->_flags       = $flags;
		$this->_parentModel = $parentModel;

		if(!is_null($parentModel)) $this->_parentClass = \get_class($parentModel);

		$this->_statement = \P3::getDatabase()->query($builder->getQuery());
	}

	/**
	 * Counts number of containing records
	 *
	 * (Required by \Counatable)
	 *
	 * @return int Number of records
	 */
	public function count()
	{
		if($this->complete()) {
			return count($this->_data);
		} else {
			$stmnt = \P3::getDatabase()->query($this->_countQuery());

			if(!$stmnt)
				return 0;

			return (int)$stmnt->fetchColumn();
		}
	}

	public function collect($attr)
	{
		$ret = array();

		foreach($this as $record)
			$ret[] = $record->{$attr};

		return $ret;
	}

	public function complete()
	{
		return $this->_state & STATE_COMPLETE;
	}

	public function current()
	{
		return $this->_data[count($this->_data)-1];
	}

	public function exists()
	{
		if($this->_flags & FLAG_SINGLE_MODE) {
			return $this->count() == 1;
		} else {
			throw new \P3\Exception\ActiveRecordException('Calling exists on a collection.  Use count() instead');
		}
	}

	public function export()
	{
		return $this->_data;
	}

	public function fetch()
	{
		if(!$this->started())
			$this->_setState(STATE_STARTED);

		if($this->_flags & FLAG_DYNAMIC_TYPES) {
			$tmp  = $this->_statement->fetch(\PDO::FETCH_ASSOC);

			if(!$tmp) {
				$record = false;
			} else {
				$type = $tmp['type'];
				$record = new $type($tmp);
			}
		} else {
			$class = \is_null($this->_contentClass) ? $this->_builder->getFetchClass() : $this->_contentClass;

			if(\is_null($class)) {
				$this->_statement->setFetchMode(\PDO::FETCH_ASSOC);
			} else {
				$this->_statement->setFetchMode(\PDO::FETCH_CLASS, $class);
			}
			$record =  $this->_statement->fetch();
		}

		if($record !== FALSE) {
			$this->_data[++$this->_fetchPointer] = $record;
		} else {
			$this->_setState(STATE_COMPLETE);
		}

		return $record;
	}

	public function filter($closure)
	{
	}

	public function first()
	{
		if(!isset($this->_data[0])) {
			$first = $this->fetch();
		}

		return $first;
	}

	public function getContentClass()
	{
		return $this->_contentClass;
	}

	public function getController()
	{
		$class = $this->_contentClass;
		return $class::$_controller;
	}

	/**
	 * Returns iterator to use
	 *
	 * (Required by \IteratorAggregate)
	 *
	 * @return \P3\ActiveRecord\Collection\Iterator
	 */
	public function getIterator()
	{
		return new \P3\ActiveRecord\Collection\Iterator($this);
	}

	public function inProgress()
	{
		return $this->started() && !$this->complete();
	}

	public function inSingleMode()
	{
		return (bool)($this->_flags & FLAG_SINGLE_MODE);
	}

	public function setContentClass($class)
	{
		$this->_contentClass = $class;
	}

	/**
	 * Determines if record is set at given offset
	 *
	 * (Required by \ArrayAccess)
	 *
	 * @param mixed $offset Key to check
	 *
	 * @return boolean True if already set, false otherwise
	 */
	public function offsetExists($offset) {
		return isset($this->_data[$offset]);
	}

	/**
	 * Retrive item at given $offset key
	 *
	 * (Required by \ArrayAccess)
	 *
	 * @param mixed $offset Key of item to grab
	 *
	 * @return mixed Returns item at given $offset, or null
	 */
	public function offsetGet($offset) {
		if(!isset($this->_data[$offset])) {
			while(!$this->complete() && $this->_fetchPointer < $offset) {
				$this->fetch();
			}
		}

		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	/**
	 * Set item at $offset key
	 *
	 * (Required by \ArrayAccess)
	 *
	 * @param mixed $offset Key to set
	 * @param mixed $value  Value to set
	 *
	 * return void
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->_data[] = $value;
		} else {
			$this->_data[$offset] = $value;
		}
	}

	/**
	 * Unset array key
	 *
	 * (Required by \ArrayAccess)
	 *
	 * @param mixed $offset Unset given $offset key
	 */
	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
	}

	public function setFlag($flag)
	{
		$this->_flags = $this->_flags | $flag;
	}

	public function started()
	{
		return (bool)$this->_state & STATE_STARTED;
	}

//- Private
	private function _countQuery() 
	{
		if(is_null($this->_countQuery)) {
			$builder = clone $this->_builder;
			$this->_countQuery = $builder->select('COUNT(*)')->getQuery();
		}

		return $this->_countQuery;
	}

	private function _fetchAll()
	{
		$this->_setState(STATE_STARTED);

		$ret = $this->_builder->fetchAll();

		if(!$ret) {
			/* Todo:  Decide WTF to do here */
		} else {
			$this->_data = $ret;
		}

		$this->_setState(STATE_COMPLETE);
	}

	private function _setState($state)
	{
		$this->_state = $this->_state | $state;
	}

//- Magic
	public function __call($name, $args)
	{
		if($this->_flags & FLAG_SINGLE_MODE) {
			if(!$this->complete()) {
				$this->_fetchAll();
			}

			if(count($this->_data) == 1)
				return call_user_func_array(array($this->_data[0], $name), $args);
			else 
				var_dump("NEED EXCEPTION HERE"); die;
		}
	}

	public function __get($name)
	{
		if($this->_flags & FLAG_SINGLE_MODE) {
			if(!$this->complete()) {
				$this->_fetchAll();
			}

			if(count($this->_data) == 1)
				return $this->_data[0]->{$name};
			else 
				var_dump("NEED EXCEPTION HERE"); die;
		}
	}

}
?>