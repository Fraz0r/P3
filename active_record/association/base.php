<?php

namespace P3\ActiveRecord\Association;
use P3\Builder\Sql as SqlBuilder;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \P3\ActiveRecord\Collection
{
	protected $_model;
	protected $_property;

	public function __construct($model, $property, SqlBuilder $builder, $fetch_class = null)
	{
		$this->_model    = $model;
		$this->_property = $property;

		parent::__construct($builder, $fetch_class);
	}

	public function get_model()
	{
		return $this->_model;
	}

	public function get_property()
	{
		return $this->_property;
	}
}

?>