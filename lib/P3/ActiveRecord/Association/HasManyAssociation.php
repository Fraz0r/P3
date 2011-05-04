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

		if(isset($options['conditions'])) {
			foreach($options['conditions'] as $k => $v) {
				if(!is_numeric($k) && !is_array($v))
					$builder->where($k.' = \''.$v.'\'');
				else
					$builder->where($v);
			}
		}

		if(isset($options['order']))
			$builder->order($options['order']);

		$flags = 0;

		if($class::$_extendable) {
			$flags = $flags | \P3\ActiveRecord\Collection\FLAG_DYNAMIC_TYPES;
		}

		parent::__construct($builder, $parent, $flags);

		$this->_contentClass = $class;
	}
}

?>