<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * Description of HasAny
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class HasAny extends Base
{
	public function __construct($parent, array $options = array(), $flags = 0)
	{
		$this->_options = $options;

		$class = isset($options['class']) ? $options['class'] : false;

		if(!$class) {
			$table = isset($options['table']) ? $options['table'] : false;
		} else {
			$table = $class::table();
		}

		if(!$table)
			throw new \P3\Exception\ActiveRecordException("Can't determine table to pull from");


		$builder = new QueryBuilder($table, null, $class);

		$builder->select();

		if(isset($options['fk'])) {
			$builder->where($table.'.'.$options['fk'].' = '.$parent->id());

			if($class::$_extendable) {
				$parents = class_parents($class, false);

				if(current($parents) != 'P3\ActiveRecord\Base')
					$builder->where('type = \''.$class.'\'', QueryBuilder::MODE_APPEND);
			}
		} elseif(isset($options['as'])) {
			$as = $options['as'];
			$builder->where($as.'_id = '.$parent->id().' AND '.$as.'_type =  \''.get_class($parent).'\'');
		} elseif(isset($options['through'])) {
			$assoc = $parent->getAssociationForField($options['through']);

			if(!$assoc)
				throw new \P3\Exception\ActiveRecordException("No association for through option");


			throw new \P3\Exception\ActiveRecordException("Unfinished support for 'through' option.  Its a doozy");
		} else {
			if(!isset($options['conditions']))
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

		if(isset($options['order']))
			$builder->order($options['order']);

		if($class && $class::$_extendable)
			$flags = $flags | \P3\ActiveRecord\Collection\FLAG_DYNAMIC_TYPES;

		parent::__construct($builder, $parent, $flags);

		$this->_contentClass = $class;
	}
}

?>
