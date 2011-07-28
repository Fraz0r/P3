<?php

namespace P3\ActiveRecord\Association;

/**
 * Returns a collection of models belonging to $parent in __construct
 * 
 * Options:
 * 	class:  Class to instantiate
 * 	fk:     Foreign Key Collumn (on child table)
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
class HasManyAssociation extends HasAny
{
	/**
	 * Instantiates a new HasManyAssociation
	 * 
	 * This is never to be called outside of P3s internal structure.
	 * 
	 * @param type $model parent in the association
	 * @param array $options options
	 */
	public function __construct($parent, array $options = array())
	{
		parent::__construct($parent, $options);
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