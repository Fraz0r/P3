<?php

namespace P3\ActiveRecord\Association;

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
class HasOneAssociation extends HasAny
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
		parent::__construct($parent, $options, \P3\ActiveRecord\Collection\FLAG_SINGLE_MODE);
	}
}

?>