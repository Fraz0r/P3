<?php

namespace P3\ActiveRecord\Association;

/**
 * Description of has_many
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class HasAny extends Base
{
	protected $_options = [];
	protected $_fk;

	public function build(array $data = [])
	{
		$class = $this->get_fetch_class();
		return new $class(array_merge([$this->_fk => $this->get_parent()->id()]));
	}

	public function get_fk()
	{
		return $this->_fk;
	}

	public function get_parent()
	{
		return parent::get_model();
	}

	protected function _get_builder()
	{
	}
}

?>