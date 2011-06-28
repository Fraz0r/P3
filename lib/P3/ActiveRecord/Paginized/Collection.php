<?php

namespace P3\ActiveRecord\Paginized;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * This is the collection class used if ->paginate() is called on any Collection
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Collection
 * @version $Id$
 */
class Collection extends \P3\ActiveRecord\Collection\Base
{
	/**
	 * Array of options
	 * 
	 * @var array
	 */
	protected $_options = array();

	/**
	 * Cached full count
	 * 
	 * @var int
	 */
	private $_fullCount = null;

	/**
	 * Cached query string for the full colection (excluding limit/offset)
	 * 
	 * @var string
	 */
	private $_fullCountQuery = null;

	/**
	 * Pages for the collection, not set until pages() is called
	 * 
	 * @see pages()
	 * @var int
	 */
	private $_pages = null;

//- Public
	/**
	 * Instantiate paginized collection
	 * 
	 * (Instantiation is generally handled internally.)
	 * 
	 * 	Ex:
	 * 		$this->photos = $user->photos->paginized($options);
	 * 
	 * 	Options:
	 * 		page     = current "page" (used to build offset clause)
	 * 		per_page = Items "per page" (used as limit clause)
	 * 	
	 * 
	 * @param P3\Database\Query\Builder $builder Query builder from parent collection
	 * @param array $options array of options
	 * @param P3\ActiveRecord\Base $parentModel parent model, if any
	 * @param int $flags flags to pass to the parent collection
	 */
	public function __construct($builder, array $options, $parentModel = null, $flags = 0)
	{
		$options['page'] = isset($options['page']) ? (int)$options['page'] : 1;


		if(isset($options['order']))
			$builder->order($options['order']);

		$this->_options = $options;

		parent::__construct($builder, $parentModel, $flags);

		$builder = $this->getBuilder();

		if($this->_options['page'] > $this->pages())
			$this->_options['page'] = $this->pages();

		if($this->_options['page'] < 1)
			$this->_options['page'] = 1;

		$limit  = $this->_options['per_page'];
		$offset = (($this->_options['page'] - 1) * $limit);

		$builder->limit($limit, $offset);

		$this->setBuilder($builder);

		\P3\Loader::loadHelper('pagination'); 
	}

	/**
	 * Counts records in collection.  Can count current "page" or entire collection
	 * using $use_full_set
	 * 
	 * (Overriden from Collection\Base count to be able to count the unpaged query)
	 * 
	 * @param boolean $use_full_set counts items in current "page" by default.  Counts full collection if true
	 * @return int count of records
	 */
	public function count($use_full_set = false)
	{
		if(!$use_full_set)
			return parent::count();

		return $this->fullCount();
	}

	/**
	 * Returns the count of all the records in the collection, w/o considering 
	 * the pages.  (Removes the limit and offset from the query and returns it)
	 * 
	 * @return int count
	 */
	public function fullCount()
	{
		if(is_null($this->_fullCount)) {
			$stmnt = \P3::getDatabase()->query($this->_fullCountQuery());

			if(!$stmnt)
				return 0;

			$this->_fullCount = !$stmnt ? 0 : (int)$stmnt->fetchColumn();
		}

		return $this->_fullCount;
	}

	/**
	 * Returns current page
	 * 
	 * @return int current page
	 */
	public function page()
	{
		return $this->_options['page'];
	}

	/**
	 * Returns the number of pages for the collection
	 * 
	 * @return int number of pages for the collection
	 */
	public function pages()
	{
		if(is_null($this->_pages))
			$this->_pages = (int)ceil($this->count(true) / $this->_options['per_page']);

		return $this->_pages;
	}

//- Protected
	/**
	 * Returns query to use to count records.
	 * 
	 * This is overriden from Collection\Base to use a sub-query of the limit clause'd SELECT COUNT(*) statement
	 * 
	 * @return string query to use if count() is called on collection, and it's not in a COMPLETEd state
	 */
	protected function _countQuery() 
	{
		if(is_null($this->_countQuery)) {
			$builder = new QueryBuilder;
			$builder->select('COUNT(*)')->selectFrom($this->_builder);
			$this->_countQuery = $builder->getQuery();
		}

		return $this->_countQuery;
	}

	/**
	 * Returns the count of all records in the collection
	 * 
	 * This method builds a query based off the select statement used for the collection,
	 * converts it to a count(*) statement, strips the limit/offset, and returns the 
	 * result.
	 * 
	 * @return type 
	 */
	protected function _fullCountQuery() 
	{
		if(is_null($this->_fullCountQuery)) {
			$builder = clone $this->_builder;
			$builder
				->select('COUNT(*)')
				->clearSection('limit')
				->clearSection('offset');

			$this->_fullCountQuery = $builder->getQuery();
		}

		return $this->_fullCountQuery;
	}
}

?>