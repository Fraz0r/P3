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
	/**
	 * @var type array
	 */
	protected $_options = array();
}

?>