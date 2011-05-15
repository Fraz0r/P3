<?php

namespace P3\ActiveRecord\Collection;

/**
 * This is the Iterator returned by P3\ActiveRecord\Collection\Base::getIterator()
 * It is used to call fetch on the PDO only as needed (avoiding large unnecessary arrays),
 * and to use a count query instead of loading all records and counting them
 * 
 * All Functionality is within Collection\Base, this just forwards all methods back into
 * the collection
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Collection
 * @version $Id$
 */
class Iterator implements \Iterator
{ 
	/**
	 * Collection this iterator is servicing
	 * 
	 * @var P3\ActiveRecord\Collection\Base
	 */
	private $_collection = null;

//- Public
	/**
	 * Instantiates new Iterator
	 * 
	 * @param type $collection collection to service
	 */
	public function __construct($collection)
	{
		$this->_collection = $collection;
	}

	/**
	 * Forwards next() back into collection
	 * 
	 * @return void
	 * @see P3\ActiveRecord\Collection\Base::next()
	 */
	public function next()
	{ 
		return $this->_collection->next();
	}

	/**
	 * Forwards current() back into collection
	 * 
	 * @return void
	 * @see P3\ActiveRecord\Collection\Base::current()
	 */
	public function current ()
	{
		return $this->_collection->current();
	}

	/**
	 * Forwards key() back into collection
	 * 
	 * @return void
	 * @see P3\ActiveRecord\Collection\Base::key()
	 */
	public function key ()
	{
		return $this->_collection->key();
	}

	/**
	 * Forwards rewind() back into collection
	 * 
	 * @return void
	 * @see P3\ActiveRecord\Collection\Base::rewind()
	 */
	public function rewind ()
	{
		return $this->_collection->rewind();
	}

	/**
	 * Forwards valid() back into collection
	 * 
	 * @return void
	 * @see P3\ActiveRecord\Collection\Base::valid()
	 */
	public function valid ()
	{
		return $this->_collection->valid();
	}
}
?>