<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * Description of HasOneAssociation
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class HasOneAssociation extends Base 
{
	public function __construct($parent, array $options = array())
	{
		$this->_options = $options;

		$class = $options['class'];

		$builder = new QueryBuilder($class::table(), null, $class);
		$builder->select()->where($options['fk'].' = '.$parent->id());


		$flags = \P3\ActiveRecord\Collection\FLAG_SINGLE_MODE;

		if($class::$_extendable) {
			$flags = $flags | \P3\ActiveRecord\Collection\FLAG_DYNAMIC_TYPES;
		}

		parent::__construct($builder, $parent, $flags);

		$this->_contentClass = $class;
	}
}

?>