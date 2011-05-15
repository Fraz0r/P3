<?php

namespace P3\ActiveRecord\Association;
use       P3\ActiveRecord\Collection;
use       P3\Database\Query\Builder as QueryBuilder;


/**
 * This is the association returned by an ActiveRecord if the "polymorphic" option
 * was passed as TRUE in BelongsTo
 * 
 * The "as" option on the HasoOne or HasMany side determines the name for two collumns that
 * must exist within the database on the BelongsTo side.
 * 
 * The example below will cause P3 to use messageable_id AND messageable_type in `messages`.
 * 
 * Example:  $this->_hasMany('messages' => array('class' => 'Message', 'as' => 'messageable'));
 * 
 * messageable_id holds the PK id for the model, and messageable_type holds the name of the model
 * classa.
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3
 * @version $Id$
 */
class PolymorphicAssociation extends Base
{
	/**
	 * Instantiates a new PolymorphicAssoctiation
	 * 
	 * This is never to be called outside of P3s internal structure.
	 * 
	 * @param type $model parent in the association
	 * @param array $options options
	 */
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