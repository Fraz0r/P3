<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * Description of HasManyAssociationa
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class HasManyAssociation extends Base
{
	public function __construct($parent, array $options = array())
	{
		$this->_options = $options;

		$class = $options['class'];

		$builder = new QueryBuilder($class::table(), null, $class);
		$builder->select()->where($options['fk'].' = '.$parent->id());

		parent::__construct($builder, $parent);

		$this->_contentClass = $class;
	}
}

?>