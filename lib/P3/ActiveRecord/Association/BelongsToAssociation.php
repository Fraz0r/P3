<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;


/**
 * Returns Collection (with SINGLE flag set) for the parent model of $model (passed in __construct())
 * 
 * Options:
 * 	class:  Class to instantiate
 * 	fk:     Foreign Key Collumn (self side)
 *
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
class BelongsToAssociation extends Base 
{
	/**
	 * Instantiates a new BelongsToAssociation
	 * 
	 * This is never to be called outside of P3s internal structure.
	 * 
	 * @param type $model parent in the association
	 * @param array $options options
	 */
	public function __construct($model, array $options = array())
	{
		$this->_options = $options;

		$class = $options['class'];

		$builder = new QueryBuilder($class::table(), null, $class);
		$builder->select()->where($class::pk().' = '.$model->{$options['fk']});

		$flags = \P3\ActiveRecord\Collection\FLAG_SINGLE_MODE;

		if($class::$_extendable) {
			$flags = $flags | \P3\ActiveRecord\Collection\FLAG_DYNAMIC_TYPES;
		}

		parent::__construct($builder, null, $flags);

		$this->_contentClass = $class;
	}
}

?>