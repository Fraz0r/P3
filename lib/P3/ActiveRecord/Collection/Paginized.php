<?php

namespace P3\ActiveRecord\Collection;
use P3\Database\Query\Builder as QueryBuilder;

/**
 * Description of Paginized
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Paginized extends Base
{

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