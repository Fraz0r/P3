<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * Description of HasAndBelongsToMany
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class HasAndBelongsToMany extends Base
{
	public function __construct($parent, array $options = array())
	{
		$this->_options = $options;

		$class = $options['class'];

		$builder = new QueryBuilder($class::table(), 'a', $class);

		$builder
			->select('a.*')
			->join($options['table'].' b', 'b.'.$options['efk'].' = a.'.$parent::pk())
			->where('b.'.$options['fk'].' = '.$parent->id());

		parent::__construct($builder, $parent);

		$this->_contentClass = $class;
	}
}

?>