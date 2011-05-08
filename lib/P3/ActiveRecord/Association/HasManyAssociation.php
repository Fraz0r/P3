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
		} elseif(isset($options['through'])) {
			$assoc = $parent->getAssociationForField($options['through']);

			if(!$assoc)
				throw new \P3\Exception\ActiveRecordException("No association for through option");


			throw new \P3\Exception\ActiveRecordException("Unfinished support for 'through' option.  Its a doozy");
		} else {
			throw new \P3\Exception\ActiveRecordException("Not enough info to retrieve association");
		}

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

	public function buildThrough($assoc)
	{
	}

	public function offsetSet($offset, $model)
	{
		/* The parent doesn't do anything with this, but it does handle the exception if offset is not null */
		parent::offsetSet($offset, $model);

		/* TODO:  Need to throw exception if the class isn't allowed in here (Handling extensions as well) */

		$fk_val = $this->_parentModel->id();

		$model->{$this->_options['fk']} = $fk_val;
		$model->save();
	}

	public function offsetUnset($offset)
	{
		if(!isset($this->_data[$offset]))
			while(!$this->complete() && $this->_fetchPointer < $offset && $this->fetch());


		/* If it's still not set, throw an exception */
		if(!isset($this->_data[$offset]))
			throw new \P3\Exception\ActiveRecordException("Can't remove model from association because it doesn't exist");

		$model = $this->_data[$offset];

		$model->{$this->_options['fk']} = 'NULL';
		$model->save();
	}
}

?>