<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * HasOne works just as HasMany, except only one record may exist on the child's side.
 * 
 * Options:
 * 	class:  Class to instantiate
 * 	fk:     Foreign Key Collumn (on child table)
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
class HasOneAssociation extends Base 
{
	/**
	 * Instantiates a new HasOneAssociation
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

		$builder = new QueryBuilder($class::table(), null, $class);

		$builder->select();

		if(isset($options['fk'])) {
			$builder->where($options['fk'].' = '.$parent->id());

			if($class::$_extendable) {
				$parents = class_parents($class, false);

				if(current($parents) != 'P3\ActiveRecord\Base')
					$builder->where('type = \''.$class.'\'', QueryBuilder::MODE_APPEND);
			}

		} elseif(isset($options['as'])) {
			$as = $options['as'];
			$builder->where($as.'_id = '.$parent->id().' AND '.$as.'_type =  \''.get_class($parent).'\'');
		} else {
			throw new \P3\Exception\ActiveRecordException("Not enough info to retrieve association");
		}


		if(isset($options['conditions'])) {
			foreach($options['conditions'] as $k => $v) {
				if(!is_numeric($k) && !is_array($v))
					$builder->where($k.' = \''.$v.'\'', QueryBuilder::MODE_APPEND);
				else
					$builder->where($v, QueryBuilder::MODE_APPEND);
			}
		}


		$flags = \P3\ActiveRecord\Collection\FLAG_SINGLE_MODE;

		if($class::$_extendable) {
			$flags = $flags | \P3\ActiveRecord\Collection\FLAG_DYNAMIC_TYPES;
		}

		parent::__construct($builder, $parent, $flags);

		$this->_contentClass = $class;
	}
}

?>