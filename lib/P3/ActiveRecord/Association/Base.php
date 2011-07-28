<?php

namespace P3\ActiveRecord\Association;
use       P3\Database\Query\Builder as QueryBuilder;


/**
 * This is not to be instantiated.  It is the base class for all Assoctiations
 * within the namespace
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
abstract class Base extends \P3\ActiveRecord\Collection\Base 
{
	public function __construct(QueryBuilder $builder, $parentModel = null, $flags = 0)
	{
		parent::__construct($builder, $parentModel, $flags);
	}
}

?>