<?php

namespace P3\ActiveRecord\Collection;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * This is the collection class used if ->paginate() is called on any Collection
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Collection
 * @version $Id$
 */
class Paginized extends Base
{
//- Public
	public function __construct($builder, array $options, $parentModel = null, $flags = 0)
	{

		$limit  = $options['per_page'];
		$offset = (($options['page'] - 1) * $limit);

		$builder->limit($limit, $offset);

		if(isset($options['order']))
			$builder->order($options['order']);

		parent::__construct($builder, $parentModel, $flags);

		\P3\Loader::loadHelper('pagination'); 
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
}

?>