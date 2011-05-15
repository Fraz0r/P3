<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * HasAndBelongsToMany exists to establish many-to-many associations.  For this
 * association to exist, a join table is required to sit in between the two associating
 * models.  Such a table typically only has 2 fields, (fk from first and second table).
 * Both these collumn names(fk, efk) must be included in the options, along with the name
 * of the join table 
 * 
 * Options:
 * 	class:  Class to instantiate
 * 	table:  Join Table
 * 	fk:     Foreign Key Collumn (self side)
 * 	efk:    Extended Foreign Key (other side)
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
class HasAndBelongsToMany extends Base
{
	/**
	 * Instantiates a new HasAndBelongsToMany
	 * 
	 * This is never to be called outside of P3s internal structure.
	 * 
	 * @param type $model parent in the association
	 * @param array $options options
	 */
	public function __construct($parent, array $options = array())
	{
		$this->_options = $options;

		$class = $options['class'];

		$builder = new QueryBuilder($class::table(), 'a', $class);

		$builder
			->select('a.*')
			->join($options['table'].' b', 'b.'.$options['efk'].' = a.'.$parent::pk())
			->where('b.'.$options['fk'].' = '.$parent->id());

		$flags = 0;

		if($class::$_extendable) {
			$flags = $flags | \P3\ActiveRecord\Collection\FLAG_DYNAMIC_TYPES;
		}

		parent::__construct($builder, $parent, $flags);

		$this->_contentClass = $class;
	}
}

?>