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
	
	protected $_fetchPointer = -1;
	protected $_indexPointer = 0;

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
	}

	public function all(array $options = array())
	{
		$builder = clone $this->_builder;

		$class = $this->_options['class'];
		$order    = (isset($options['order']) && !is_null($options['order'])) ? $options['order'] : $class::pk().' ASC';
		$only_one = isset($options['one']) ? $options['one'] : false;
		$limit    = isset($options['limit']) ? $options['limit'] : null;
		$flags    = 0;

		if($only_one) {
			$limit = 1;
			$flags = $flags | FLAG_SINGLE_MODE;
		}

		if($class::$_extendable)
			$flags = $flags | FLAG_DYNAMIC_TYPES;

		$builder->select();

		if(!isset($options['conditions'])) {
			$builder->where('1');
		} else {
			foreach($options['conditions'] as $k => $v) {
				if(!is_numeric($k) && !is_array($v))
					$builder->where($k.'=\''.$v.'\'', QueryBuilder::MODE_APPEND);
				else {
					$builder->where($v, QueryBuilder::MODE_APPEND);
				}
			}
		}

		if($class::$_extendable) {
			$parent_class = array_shift(class_parents($class));

			if($parent_class !== __CLASS__)
				$builder->where('type = \''.$class.'\'', QueryBuilder::MODE_APPEND);
		} 

		$builder->order($order);

		if(!is_null($limit)) {
			if(!is_array($limit))
				$offset = null;
			else
				list($limit, $offset) = $limit;

			$builder->limit($limit, $offset);
		}

		$collection = new self($builder, null, $flags);


		return $only_one ? $collection->first() : $collection;
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

	public function collect($attr, array $args = array())
	{
		$is_func = $attr[0] == ':';
		$ret     = array();

		if($is_func)
			$func = substr($attr, 1);

		foreach($this as $record)
			$ret[] = $is_func ? call_user_func_array(array($record, $func), $args) : $record->{$attr};

		return $ret;
	}

	public function complete()
	{
		return $this->_state & STATE_COMPLETE;
	}

	public function current()
	{
		return $this[$this->_indexPointer];
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
		if(!$this->complete())
			$this->_fetchAll();

		return $this->_data;
	}

	public function fetch()
	{
		if(!$this->started())
			$this->_start();

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

	/**
	 * Finds the record(s) within the collection 
	 * 
	 * @param type $where where for ActiveRecords find
	 * @param array $options options 
	 */
	public function find($id, array $options = array())
	{
		/* TODO: Same code from ActiveRecord's find (minus extension checks) (needs to be refactored) */

		$builder = clone $this->_builder;

		$order    = (isset($options['order']) && !is_null($options['order'])) ? $options['order'] : static::pk().' ASC';
		$limit    = isset($options['limit']) ? $options['limit'] : null;
		$flags    = 0;
		$class = $this->_options['class'];

		$only_one = true;

		if($only_one) {
			$limit = 1;
			$flags = $flags | FLAG_SINGLE_MODE;
		}

		/* Uses MODE_PREPEND to attempt to preserve indexed keys */
		$builder->where($class::pk().' = '.$id, QueryBuilder::MODE_PREPEND);

		if(isset($options['conditions'])) {
			foreach($options['conditions'] as $k => $v) {
				if(!is_numeric($k) && !is_array($v))
					$builder->where($k.'=\''.$v.'\'', QueryBuilder::MODE_APPEND);
				else {
					$builder->where($v, QueryBuilder::MODE_APPEND);
				}
			}
		}

		if(!is_null($limit)) {
			if(!is_array($limit))
				$offset = null;
			else
				list($limit, $offset) = $limit;

			$builder->limit($limit, $offset);
		}

		$collection = new self($builder, null, $this->_flags | $flags);

		return $only_one ? $collection->first() : $collection;
	}

	public function first()
	{
		if(!isset($this->_data[0]))
			return $this->fetch();

		return $this->_data[0];
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

	public function key()
	{
		return $this->_indexPointer;
	}

	public function last()
	{
		if(count($this)) {
			if(!$this->complete())
				$this->_fetchAll();

			return $this->_data[count($this) - 1];
		}

		return null;
	}

	public function next()
	{
		if(!$this->complete())
			$this->fetch();

		$this->_indexPointer++;
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
	public function offsetExists($offset) 
	{
		while(!$this->complete() && $this->_fetchPointer < $offset && $this->fetch());

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
		if(!isset($this->_data[$offset]))
			while(!$this->complete() && $this->_fetchPointer < $offset && $this->fetch());

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
	public function offsetSet($offset, $value) 
	{
		if(!is_null($offset))
			throw new \P3\Exception\ActiveRecordException("Replacing a model in the collection is not supported (You passed a key to set into the collection array).  You should add models using \$collection[].");
	}

	/**
	 * Unset array key
	 *
	 * (Required by \ArrayAccess)
	 *
	 * @param mixed $offset Unset given $offset key
	 */
	public function offsetUnset($offset) {
		if(!isset($this->_data[$offset]))
			while(!$this->complete() && $this->_fetchPointer < $offset && $this->fetch());

		unset($this->_data[$offset]);
	}

	public function paginate(array $options)
	{
		return new Paginized(clone $this->_builder, $options, $this->_parentModel, $this->_flags);
	}


	public function rewind()
	{
		$this->_indexPointer = 0;
	}

	public function setFlag($flag)
	{
		$this->_flags = $this->_flags | $flag;
	}

	public function started()
	{
		return (bool)$this->_state & STATE_STARTED;
	}

	public function valid()
	{
		if($this->started())
			return !$this->complete() || $this->_indexPointer < count($this);
		else
			return (bool)count($this);
	}

//- Protected
	protected function _countQuery() 
	{
		if(is_null($this->_countQuery)) {
			$builder = clone $this->_builder;
			$this->_countQuery = $builder->select('COUNT(*)')->getQuery();
		}

		return $this->_countQuery;
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

	private function _start()
	{
		$this->_setState(STATE_STARTED);
		$this->_statement = \P3::getDatabase()->query($this->_builder->getQuery());
	}

//- Magic
	public function __call($name, $args)
	{
		/* TODO:  This is old and needs some work */
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
		/* TODO:  This is old and needs some work */
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