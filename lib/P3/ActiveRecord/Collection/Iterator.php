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
	}

	public function current ()
	{
		return $this->_collection->current();
	}

	public function key ()
	{
	}

	public function rewind ()
	{
	}

	public function valid ()
	{
		return !$this->_collection->complete() && FALSE !== $this->_collection->fetch();
	}
}
?>