<?php

namespace P3\ActiveRecord\Collection;

/**
 * Description of Iterator
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Iterator implements \Iterator
{ 
	private $_collection = null;

	public function __construct($collection)
	{
		$this->_collection = $collection;
	}

	public function next()
	{ 
		return $this->_collection->next();
	}

	public function current ()
	{
		return $this->_collection->current();
	}

	public function key ()
	{
		return $this->_collection->key();
	}

	public function rewind ()
	{
		return $this->_collection->rewind();
	}

	public function valid ()
	{
		return $this->_collection->valid();
	}
}
?>