<?php

namespace P3\ActiveRecord\Collection;
use       P3\Database\Query\Builder as QueryBuilder;

const FLAG_NORMAL_MODE = 1;
const FLAG_SINGLE_MODE = 2;

const STATE_STARTED  = 1;
const STATE_COMPLETE = 2;

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

//- Public
	/**
	 * Construct
	 *
	 * @param array $input Array of records
	 * @param P3\ActiveRecord\Base $parent Parent model, if any
	 */
	public function __construct(QueryBuilder $builder, $parentModel = null, $flags = null)
	{
		$this->_builder     = $builder;
		$this->_flags       = !is_null($flags) ? $flags : FLAG_NORMAL_MODE;
		$this->_parentModel = $parentModel;

		if(!is_null($parentModel)) $this->_parentClass = \get_class($parentModel);

		/* TEMPORARY */
		$this->_fetchAll();
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
		return count($this->_data);
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
		return $this->_state & STATE_STARTED & STATE_COMPLETE;
	}

	public function exists()
	{
		if($this->_flags & FLAG_SINGLE_MODE) {
			return $this->count() == 1;
		} else {
			throw new \P3\Exception\ActiveRecordException('Calling exists on a collection.  Use count() instead');
		}
	}

	public function fetch()
	{
		if($this->inSingleMode()) {
			/* Todo:  This will need to change */
			return isset($this->_data[0]) ? $this->_data[0] : false;
		}
	}

	public function filter($closure)
	{
	}

	public function first()
	{
		if(!$this->complete()) {
			/* TODO:  Switch this to fetch only one record */
			$this->_fetchAll();
		}
		return isset($this->_data[0]) ? $this->_data[0] : false;
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
		return new \P3\ActiveRecord\Collection\Iterator($this->_data);
	}

	public function inProgress()
	{
		return $this->started() && !$this->complete();
	}

	public function inSingleMode()
	{
		return (bool)($this->_flags & FLAG_SINGLE_MODE);
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

	public function started()
	{
		return (bool)$this->_state & STATE_STARTED;
	}

//- Private
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