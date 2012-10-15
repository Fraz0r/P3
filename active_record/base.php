<?php

namespace P3\ActiveRecord;
use P3\Builder\Sql as SqlBuilder;
use P3\Database\Table\Column as TableColumn;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \P3\Model\Base
{
	protected static $_pk = 'id';
	protected static $_table;

	public function __construct(array $data = [])
	{
		parent::__construct($data);
	}

//- Protected
	protected function _read_attribute($attribute)
	{
		$attr_info = static::get_attr_info($attribute);

		return $attr_info->parsed_read(parent::_read_attribute($attribute));
	}

//- Static
	public static function all(array $options = [])
	{
		if(isset($options['select'])) {
			$fields = $options['select'];
			unset($options['select']);
		} else {
			$fields = '*';
		}

		$builder = new SqlBuilder(static::get_table());
		$builder->select($fields);

		foreach($options as $k => $v)
			$builder->{$k}($v);

		return new Collection($builder, get_called_class());
	}

	public static function find($id_or_what, array $options_if_what = [])
	{
		if(is_numeric($id_or_what))
			return self::all()->where([static::$_pk => $id_or_what])->limit(1)->fetch();

		$ret = self::{$id_or_what}($options_if_what);

		return $ret;
	}

	public static function first(array $options = [])
	{
		$ret = static::all();

		foreach($options as $k => $v)
			$ret->{$k}($v);

		$ret->limit(1);

		return $ret->fetch();
	}

	public static function get_table()
	{
		if(!empty(static::$_table))
			return static::$_table;

		$class = get_called_class();

		static::$_table = \str::humanize(\str::pluralize($class));

		return static::$_table;
	}

	public static function get_attr_type($attr)
	{
		$info = static::get_attr_info($attr);

		return $info->get_type();
	}

	public static function get_attr_info($attr)
	{
		return \P3::database()->get_column_info(static::get_table(), $attr);
	}


	public static function get_table_info()
	{
		return \P3::database()->get_table_info(static::get_table());
	}

//- Magic
	public static function __callStatic($method, array $args = [])
	{
		if(substr($method, 0, 8) == 'find_by_') {
			$all   = false;
			$field = substr($method, 8);
		} elseif(substr($method, 0, 12) == 'find_all_by_') {
			$all   = true;
			$field = substr($method, 12);
		}

		// TODO: Need exceptions based on num of args/wtf is in them
		if(isset($field)) {
			$return = static::all()->where([$field => $args[0]]);

			if(isset($args[1]))
				foreach($args[1] as $k => $v)
					$return->{$k}($v);

			if(!$all)
				$return = $return->limit(1)->fetch();

			return $return;
		}

		throw new \P3\Exception\MethodException\NotFound(get_called_class(), $method);
	}
}

?>