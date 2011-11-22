<?php

namespace P3\ActiveRecord\Collection;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * Container for a collection of ActiveRecords.  Note, no query has hit the database
 * when this is returned.  Fetch() is called as the array is accessed.  count() should
 * be used to verify you have a valid collection, before accessing elements.
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Collection
 * @version $Id$
 */
class Base implements  \Iterator, \ArrayAccess , \Countable
{
//- attr-protected
	/**
	 * Query Builder to use for ActiveRecord retreival
	 * 
	 * @var type P3\Database\Query\Builder
	 */
	protected $_builder      = null;

	/**
	 * Containing model class name.  This is the class used for FETCH_INTO on the pdo
	 * 
	 * @var string 
	 */
	protected $_contentClass = null;

	/**
	 * Cache'd count, to prevent multiple COUNT(*) from firing
	 * 
	 * @var type 
	 */
	protected $_count = null;

	/**
	 * Query string used to count records
	 * 
	 * @see _countQuery
	 * @var string
	 */
	protected $_countQuery   = null;

	/**
	 * Container for loaded ActiveRecords
	 * @var array
	 */
	protected $_data         = array();

	/**
	 * Pointer for fetch()
	 * 
	 * @var int 
	 */
	protected $_fetchPointer = -1;

	/**
	 * Flags set on Collection
	 * @var int
	 */
	protected $_flags        = 0;

	/**
	 * Pointer for array access
	 * 
	 * @var int
	 */
	protected $_indexPointer = 0;

	protected $_options = array();

	/**
	 * Parent model (if any)
	 * 
	 * @var P3\ActiveRecord\Base
	 */
	protected $_parentModel  = null;

	/**
	 * Current state of collection
	 * 
	 * @var int
	 */
	protected $_state        = 0;

	/**
	 * PDO Statement used for model retreival
	 * @var \PDOStatement
	 */
	protected $_statement    = null;
	

//- Public
	/**
	 * This is to be called internally from P3 only
	 *
	 * @param P3\Database\Query\Builder $builder builder to use for retrieval
	 * @param P3\ActiveRecord\Base $parent parent model, if any
	 */
	public function __construct(QueryBuilder $builder, $parentModel = null, $flags = 0)
	{
		$this->_builder     = $builder;
		$this->_flags       = $flags;
		$this->_parentModel = $parentModel;

		if(!is_null($parentModel)) $this->_parentClass = \get_class($parentModel);
	}

	/**
	 * Search all records whithin collection
	 * 
	 * @param array $options
	 * @return mixed ActiveRecord if 'one' option is set to TRUE, Collection\Base if multiple
	 * @see \P3\ActiveRecord\Base::find()
	 */
	public function all(array $options = array())
	{
		$builder = clone $this->_builder;

		$class    = isset($this->_options['class']) ? $this->_options['class'] : $this->_contentClass;
		$order    = isset($options['order']) ? $options['order'] : false;
		$only_one = isset($options['one']) ? $options['one'] : false;
		$limit    = isset($options['limit']) ? $options['limit'] : null;
		$flags    = $this->_flags;

		if($only_one) {
			$limit = 1;
			$flags = $flags | FLAG_SINGLE_MODE;
		}

		if($class::$_extendable)
			$flags = $flags | FLAG_DYNAMIC_TYPES;

		$builder->select();

		if(!isset($options['conditions'])) {
			if(!$builder->sectionCount('where'))
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

		if($order)
			$builder->order($order);

		if(!is_null($limit)) {
			if(!is_array($limit))
				$offset = null;
			else
				list($limit, $offset) = $limit;

			$builder->limit($limit, $offset);
		}

		$collection = new self($builder, $this->_parentModel, $flags);

		if(isset($this->_contentClass) && !is_null($this->_contentClass))
			$collection->contentClass($this->_contentClass);

		return $only_one ? $collection->first() : $collection;
	}

	/**
	 * Counts number of containing records.  Uses count query if collection hasn't
	 * yet fetched all records
	 *
	 * (Required by \Counatable)
	 *
	 * @return int Number of records
	 */
	public function count()
	{
		if($this->complete()) {
			$count = count($this->_data);
		} else {
			if(is_null($this->_count)) {
				$stmnt = \P3::getDatabase()->query($this->_countQuery());

				$count = $this->_count = !$stmnt ? 0 : (int)$stmnt->fetchColumn();
			} else {
				$count = $this->_count;
			}
		}

		return $count;
	}

	/**
	 * Loops through records within self, and collections $attr field.  If
	 * $attr starts with a ':', it is collected as a function - using $args if passed.
	 * 
	 * @param string $attr attribute to retreive
	 * @param array $args arguments for function, if a symbol was pased
	 * @return array returned values
	 */
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

	/**
	 * Returns true if collection has finished fetching records (reached end of record set)
	 * 
	 * @return boolean
	 */
	public function complete()
	{
		return $this->_state & STATE_COMPLETE;
	}

	public function contentClass($class = null)
	{
		if(is_null($class))
			return $this->_contentClass;
		else
			$this->_contentClass = $class;
	}

	/**
	 * Returns current active record
	 * 
	 * @return P3\ActiveRecord\Base
	 */
	public function current()
	{
		return $this[$this->_indexPointer];
	}

	/**
	 * Determines if passed $model exists in the collection
	 *  
	 * @return boolean
	 * @throws P3\Exception\ActiveRecordException
	 */
	public function exists($model)
	{
		$flag = false;

		foreach($this as $record)
			if(TRUE === ($flag = $model->id() == $record->id()))
				break;

		return $flag;
	}

	/**
	 * Returns an array of ActiveRecords.  
	 * 
	 * Legacy functionality (this is discouraged)
	 * 
	 * @return array
	 */
	public function export()
	{
		if(!$this->complete())
			$this->_fetchAll();

		return $this->_data;
	}

	/**
	 * Fetches next ActiveRecord in record set.  If false is returned, the Collection
	 * switches it's state to COMPLETE
	 * 
	 * @return mixed ActiveRecord if successfully, false if completed
	 */
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

	/**
	 * Filters collection based on closure
	 * 
	 * @param Closure $closure function to use in filtering
	 * @todo Finish filter() in P3\ActiveRecord\Collection\Base
	 */
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

		$limit    = isset($options['limit']) ? $options['limit'] : null;
		$flags    = 0;
		$class = $this->_options['class'];
		$order    = (isset($options['order']) && !is_null($options['order'])) ? $options['order'] : $class::pk().' ASC';

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

		$collection = new self($builder, $this->_parentModel, $this->_flags | $flags);

		return $only_one ? $collection->first() : $collection;
	}

	/**
	 * Retrieves the first record in the collection
	 * 
	 * @return ActiveRecord\Base First record in collection
	 */
	public function first()
	{
		if(!isset($this->_data[0]))
			return $this->fetch();

		return $this->_data[0];
	}

	/**
	 * Returns query builder being used by collection 
	 * 
	 * @return P3\Database\Query\Builder builder being used by collection
	 */
	public function getBuilder()
	{
		return $this->_builder;
	}

	/**
	 * Returns class of child models
	 * 
	 * @return string Class of children
	 */
	public function getContentClass()
	{
		return $this->_contentClass;
	}

	/**
	 * Returns controler of child class
	 * 
	 * @return string name of controller
	 */
	public function getController()
	{
		$class = $this->_contentClass;
		return $class::$_controller;
	}

	/**
	 * Determines whether or not the collection is in progress (fetch() has started, but not reached end of record set)
	 * 
	 * @return boolean true if in progress, false otherwise
	 */
	public function inProgress()
	{
		return $this->started() && !$this->complete();
	}

	/**
	 * Determines if FLAG_SINGLE_MODE is set
	 * 
	 * @return type boolean
	 */
	public function inSingleMode()
	{
		return (bool)($this->_flags & FLAG_SINGLE_MODE);
	}

	/**
	 * Returns current key (index pointer)
	 * 
	 * @return int current index pointer
	 */
	public function key()
	{
		return $this->_indexPointer;
	}

	/**
	 * Returns last record in collecion
	 * 
	 * @return ActiveRecord\Base last record in collection
	 */
	public function last()
	{
		if(count($this)) {
			if(!$this->complete())
				$this->_fetchAll();

			return $this->_data[count($this) - 1];
		}

		return null;
	}

	/**
	 * Moves collection to next record.  Calls fetch first if not yet complete
	 * 
	 * @return void
	 */
	public function next()
	{
		if(!$this->complete())
			$this->fetch();

		$this->_indexPointer++;
	}

	/**
	 * Set content class to instantiate on fetch()
	 * 
	 * @param string $class class to intantiate models as
	 * @see fetch
	 */
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
	 * @return void
	 */
	public function offsetUnset($offset) {
		if(!isset($this->_data[$offset]))
			while(!$this->complete() && $this->_fetchPointer < $offset && $this->fetch());

		unset($this->_data[$offset]);
	}

	/**
	 * Returns a paginized version of self, based on options
	 * 
	 * @param array $options
	 * @return P3\ActiveRecord\Collection\Paginized 
	 */
	public function paginate(array $options)
	{
		return new \P3\ActiveRecord\Paginized\Collection(clone $this->_builder, $options, $this->_parentModel, $this->_flags);
	}


	/**
	 * Moves our index pointer back to the start of the collection
	 * 
	 * @return void
	 */
	public function rewind()
	{
		$this->_indexPointer = 0;
	}

	public function setBuilder($builder)
	{
		$this->_builder = $builder;
		$this->rewind();
	}

	/**
	 * Sets a flag on the collection
	 * 
	 * @param int $flag flag to set
	 */
	public function setFlag($flag)
	{
		$this->_flags = $this->_flags | $flag;
	}

	/**
	 * Determines whether or not the colletion has started fetching records
	 * 
	 * @return boolean true if started, false otherwise 
	 */
	public function started()
	{
		return (bool)$this->_state & STATE_STARTED;
	}

	public function toCSV($eol = "\n")
	{
		$this->rewind();
		$lines = array();
		
		$x = 0;
		foreach($this as $item) {
			if($x++ == 0)
				$lines[] = $item->toCSVHeader();

			$lines[] = $item->toCSV();
		}

		return implode($eol, $lines);
	}

	public function toJSON(array $fields = array())
	{
		return '['.implode(',', $this->collect(':toJSON', array($fields))).']';
	}

	/**
	 * Lets foreach() loops know when to `break`
	 * 
	 * @return boolean returns false when the end of a record set has been reached
	 */
	public function valid()
	{
		if($this->started())
			return !$this->complete() || $this->_indexPointer < count($this);
		else
			return (bool)count($this);
	}

//- Protected
	/**
	 * Returns count query to use for count().  Builds it on the first call
	 * 
	 * @return string returns query to use for count() 
	 */
	protected function _countQuery() 
	{
		if(is_null($this->_countQuery))
			$this->_countQuery = $this->getBuilder()->getCountQuery();

		return $this->_countQuery;
	}

//- Private
	/**
	 * Fetches all records and completes collection.
	 * Should be avoided unless absolutely necessary
	 * 
	 * @return void
	 */
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

	/**
	 * Sets state of collection
	 * 
	 * @param int $state sets state of collection
	 */
	private function _setState($state)
	{
		$this->_state = $this->_state | $state;
	}

	/**
	 * Instantiates PDOStatemnts used by fetch().  Called by fetch() [on first call]
	 * 
	 * @return void
	 * @see fetch
	 */
	private function _start()
	{
		$this->_setState(STATE_STARTED);
		$this->_statement = \P3::getDatabase()->query($this->_builder->getQuery());
	}

//- Magic
	public function __call($name, $arguments)
	{
		if(substr($name, 0, 8) == 'find_by_') {
			$all   = false;
			$field = substr($name, 8);
		} if(substr($name, 0, 12) == 'find_all_by_') {
			$all   = true;
			$field = substr($name, 12);
		}

		if(isset($field)) {
			if(!$all)
				$arguments['one'] = true;


			$arguments['conditions'] = array($field => array_shift($arguments));

			return $this->all($arguments);
		} else {
			if(!is_null($this->_contentClass)) {
				$class = $this->_contentClass;

				if(isset($class::$_scope[$name])) {
					$args = isset($arguments[0]) && is_array($arguments[0]) ? $arguments[0] : array();

					return $this->all(array_merge($class::$_scope[$name], $args));
				}
			}
		}

		throw new \P3\Exception\ActiveRecordException("Method doesnt exist: %s", array($name));
	}

	/**
	 * Magic Get
	 * 
	 * @param string $name var to retreive
	 * @return mixed val of var from record
	 * @magic
	 */
	public function __get($name)
	{
		return $this->{$name}();
	}

}
?>