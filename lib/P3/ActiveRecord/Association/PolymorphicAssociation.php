<?php

namespace P3\ActiveRecord\Association;
use       P3\ActiveRecord\Collection;
use       P3\Database\Query\Builder as QueryBuilder;


/**
 * Description of BelongsToAssociation
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class PolymorphicAssociation extends Base
{
	public function __construct($model, array $options = array())
	{
		$this->_options = $options;

		$as = $options['polymorphic_as'];
		$class = $model->{$as.'_type'};

		$builder = new QueryBuilder($class::table(), null, $class);
		$builder->select()->where($class::pk().' = '.$model->{$as.'_id'});

		$flags = Collection\FLAG_SINGLE_MODE;

		parent::__construct($builder, null, $flags);

		$this->_contentClass = $class;
	}
}

?>