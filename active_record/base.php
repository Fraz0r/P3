<?php

namespace P3\ActiveRecord;
use P3\Builder\Sql as SqlBuilder;

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

//- Static
	public static function all(array $options = [])
	{
		if(!isset($options['fields'])) {
			$fields = '*';
		} elseif(is_array($options['fields'])) {
			$fields = implode(', ', $options['fields']);
		} else {
			$fields = $options['fields'];
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
}

?>