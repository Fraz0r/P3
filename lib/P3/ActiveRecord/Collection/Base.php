<?php

namespace P3\ActiveRecord\Collection;

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
	protected $_contentClass = null;
	protected $_data         = array();
	protected $_parent       = null;
	protected $_parentClass  = null;

//- Public
	/**
	 * Construct
	 *
	 * @param array $input Array of records
	 * @param P3\ActiveRecord\Base $parent Parent model, if any
	 */
	public function __construct($input = null, $parent = null)
	{
		if(!is_array($input)) throw new \P3\Exception\ActiveRecordException("An array must be passed into P3\ActiveRecord\Collection\Base::__construct()");

		$this->_parent = $parent;

		if(!is_null($parent)) $this->_parentClass = \get_class($parent);
		if(count($input)) $this->_contentClass    = \get_class($input[0]);

		$this->_data = $input;
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

	public function filter($closure)
	{
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


}
?>