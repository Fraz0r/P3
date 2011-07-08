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

		$builder = new QueryBuilder($class::table(), null, $class);

		$builder
			->select($class::table().'.*')
			->join($options['table'], $options['table'].'.'.$options['efk'].' = '.$class::table().'.'.$parent::pk())
			->where($options['table'].'.'.$options['fk'].' = '.$parent->id());

		$flags = 0;

		if($class::$_extendable) {
			$flags = $flags | \P3\ActiveRecord\Collection\FLAG_DYNAMIC_TYPES;
		}

		parent::__construct($builder, $parent, $flags);

		$this->_contentClass = $class;
	}

	public function offsetSet($offset, $model)
	{
		/* The parent doesn't do anything with this, but it does handle the exception if offset is not null */
		parent::offsetSet($offset, $model);

		/* TODO:  Need to throw exception if the class isn't allowed in here (Handling extensions as well) */
		$fk_val = $this->_parentModel->id();

		$pk = $this->_parentModel->id();

		$opts = $this->_options;
		if(!$this->exists($model))
			\P3::getDatabase()->exec("INSERT INTO `{$opts['table']}`({$opts['fk']}, {$opts['efk']}) VALUES('{$pk}', '{$model->id()}')");
	}

	public function remove($model)
	{
		if(!$this->exists($model)) return false;

		$opts = $this->_options;

		$join_table = $opts['table'];
		$fk = $opts['fk'];
		$efk = $opts['efk'];

		$sql = "DELETE FROM `{$join_table}`";
		$sql .= " WHERE {$fk} = ".$this->_parentModel->id();
		$sql .= " AND {$efk} = ".$model->id();
		$sql .= " LIMIT 1";

		$stmnt = \P3::getDatabase()->query($sql);
		return (bool)$stmnt->rowCount();
	}
}

?>