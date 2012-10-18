<?php

namespace P3\ActiveRecord\Association;
use P3\Builder\Sql as SqlBuilder;

/**
 * Description of has_many
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class HasMany extends HasAny
{
	protected $_options = [];

	//TODO: refactor this into HasAny  (when you make HasOne assoc)
	public function __construct($parent, $property, array $options = [])
	{
		$fetch_class = isset($options['class']) ? $options['class'] : \str::to_camel(ucfirst(\str::singularize($property)));

		if(!isset($options['fk']))
			$this->_fk = \str::from_camel(get_class($parent)).'_id';
		else
			$this->_fk = $options['fk'];

		$builder = new SqlBuilder($fetch_class::get_table());
		$builder->select()->where([$this->_fk => $parent->id()]);

		parent::__construct($parent, $property, $builder, $fetch_class);
	}

	public function build(array $data = [])
	{
		$this->fill(); // make sure we fill the array from db before we dirty it up

		$parent = $this->get_parent();
		$child  = parent::build($data);

		$parent->{$this->get_property()}[] = $child;

		return $child;
	}

	public function set_data(array $data = [])
	{
		$child_class = $this->get_fetch_class();
		$parent_class = get_class($this->get_parent());
		$association_property = $this->get_property();
		$pk = $child_class::get_pk();

		foreach($data as $child_data) {
			$child_data[$this->get_fk()] = $this->get_parent()->id();

			if(isset($child_data[$pk])) {
				$child = $child_class::find($child_data[$pk]);

				if(isset($child_data['_delete']) && $child_data['_delete']) {
					if(isset($parent_class::$accept_nexted_attributes_for[$association_property]['allow_destroy']) && $parent_class::$accept_nexted_attributes_for[$association_property]['allow_destroy']) {
						$child->destroy();
						continue;
					} else {
						throw new \P3\Exception\ActiveRecordException('You are not allowed to call delete on the %s children of model: %s', [$child_class, $parent_class]);
					}
				} else {
					unset($child_data['_delete']);

					foreach($child_data as $k => $v)
						$child->{$k} = $v;
				}

			} else {
				$child = new $child_class($child_data);
			}

			if(!$child->save()) {
				var_dump($child); die;
				//TODO:  NEED TO ADD TO PARENT ERRORS!!!
				break;
			}
		}
	}
}

?>