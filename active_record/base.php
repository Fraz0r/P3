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
	public static $has_attachment = [];

	public static $validate_email;
	public static $validate_presence;

	protected static $_pk = 'id';
	protected static $_fixture;
	protected static $_table;

	protected $_changed = [];

	public $errors;

	public function __construct(array $data = [])
	{
		parent::__construct($data);

		$this->errors = new Errors($this);
	}

	public function add_error($error, $field = null)
	{
		$this->errors->add($error, $field);
	}

	public function id()
	{
		if(!isset($this->_data[static::$_pk]))
			return false;

		return $this->_read_attribute(static::$_pk);
	}

	public function is_dirty($field = null)
	{
		if(is_null($field))
			return (bool)count($this->_changed);

		return in_array($field, $this->_changed);
	}

	public function is_new()
	{
		return !$this->id();
	}

	public function save(array $options = [])
	{
		$loud     = !isset($options['loud']) ? false : (bool)$options['loud'];
		$validate = !isset($options['validate']) ? true : (bool)$options['validate'];
		$method    = $this->is_new() ? 'insert' : 'update';

		if($validate) {
			$this->_before_validate();
			$this->{'_before_validate_on_'.$method}();

			if(!$this->valid())
				return false;

			$this->_after_validate();
			$this->{'_after_validate_on_'.$method}();
		}

		$this->_before_save();
		$this->{'_before_'.$method}();
		$success = $this->{$this->is_new() ? '_insert' : '_update'}();

		if($success) {
			$this->{'_after_'.$method}();
			$this->_after_save();
		} elseif($loud) {
			// TODO: Finish exception here
			throw new \Exception("TODO: NEED EXCEPTION HERE.  LOUD SAVE FAIL");
		}

		return $success;
	}

	public function update_attribute($attribute, $value)
	{
		$this->{$attribute} = $value;

		return $this->save(['validate' => false]);
	}

	public function update_attributes($data)
	{
		foreach($data as $attribute => $value)
			$this->{$attribute} = $value;

		return $this->save(['validate' => false]);
	}

	public function valid()
	{
		if(!is_null(static::$validate_presence)) {
			foreach(static::$validate_presence as $k => $v) {
				$field = is_numeric($k) ? $v : $k;
				$val   = isset($this->_data[$field]) ? $this->{$field} : null;

				if(!empty($val))
					continue;

				$message = 'is required';
				
				if(FALSE === (filter_var($val, FILTER_VALIDATE_EMAIL)))
					$this->add_error($field, $message);
			}
		}
		if(!is_null(static::$validate_email)) {
			foreach(static::$validate_email as $k => $v) {
				$field = is_numeric($k) ? $v : $k;
				$val   = $this->{$field};

				if(empty($val))
					continue;

				$message = 'must be a valid email address';
				
				if(FALSE === (filter_var($val, FILTER_VALIDATE_EMAIL)))
					$this->add_error($field, $message);
			}
		}


		return !count($this->errors);
	}

//- Protected
	protected function _after_insert()
	{
		return true;
	}

	protected function _after_save()
	{
		return true;
	}

	protected function _after_update()
	{
		return true;
	}

	protected function _after_validate()
	{
		return true;
	}

	protected function _after_validate_on_insert()
	{
		return true;
	}

	protected function _after_validate_on_update()
	{
		return true;
	}

	protected function _before_insert()
	{
		return true;
	}

	protected function _before_save()
	{
		return true;
	}

	protected function _before_update()
	{
		return true;
	}

	protected function _before_validate()
	{
		return true;
	}

	protected function _before_validate_on_insert()
	{
		return true;
	}

	protected function _before_validate_on_update()
	{
		return true;
	}

	/**
	 * 
	 * @return bool
	 * 
	 * @todo cleanup adding timestamps in _insert AND _update
	 */
	protected function _insert()
	{
		if(\P3::database()->column_exists(static::get_table(), 'created_at'))
			$this->_write_attribute('created_at', 'now');
		if(\P3::database()->column_exists(static::get_table(), 'updated_at'))
			$this->_write_attribute('updated_at', 'now');

		$id = static::fixture()->write($this->_data, static::get_table(), static::$_pk);

		if($id)
			$this->_data[static::$_pk] = $id;

		return (bool)$id;
	}

	protected function _read_attribute($attribute)
	{
		$attr_info = static::get_attr_info($attribute);

		return $attr_info->parsed_read(parent::_read_attribute($attribute));
	}

	protected function _update()
	{
		if(!$this->is_dirty())
			return true;

		if(\P3::database()->column_exists(static::get_table(), 'updated_at'))
			$this->_write_attribute('updated_at', 'now');	

		$data = [static::$_pk => $this->_data[static::$_pk]];

		foreach($this->_changed as $dirty_field)
			$data[$dirty_field] = $this->_data[$dirty_field];

		$ret = static::fixture()->write($data, static::get_table(), static::$_pk);

		if($ret)
			$this->_changed = [];

		return $ret;
	}

	protected function _write_attribute($attribute, $value)
	{
		try {
			$before_val = $this->_read_attribute($attribute);
			$attr_info = static::get_attr_info($attribute);
			$value     = $attr_info->parsed_write($value);

			$ret = parent::_write_attribute($attribute, $value);
		} catch(\P3\Model\Exception\AttributeNoExist $e) {
			if(\P3::database()->column_exists(static::get_table(), $attribute))
				$this->_data[$attribute] = null;

			return $this->_write_attribute($attribute, $value);
		}

		if($before_val != $this->_read_attribute($attribute))
			$this->_changed[] = $attribute;

		return $ret;
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

	public static function fixture($fixture = null)
	{
		return is_null($fixture) ? self::get_fixture() : self::set_fixture($fixture);
	}

	public static function get_fixture()
	{
		if(is_null(self::$_fixture)) {
			$class = \P3::config()->active_record->fixture_class;
			self::$_fixture = new $class;
		}

		return self::$_fixture;
	}

	public static function get_table()
	{
		return \str::humanize(\str::pluralize(get_called_class()));
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