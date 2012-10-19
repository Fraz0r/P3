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
	public static $accept_nexted_attributes_for = [];

	public static $belongs_to  = [];
	public static $has_one     = [];
	public static $has_many    = [];

	public static $has_attachment = [];

	public static $validate_email;
	public static $validate_presence;

	protected static $_pk = 'id';
	protected static $_fixture;
	protected static $_table;

	protected $_attachment_offset = false;
	protected $_attachment_parent = false;
	protected $_attachment_prop   = false;
	private $_save_attachments = true;

	//TODO:  Need to bind the rest of the triggers this way too (to be able to dynamically add triggers)
	protected $_after_save = [];
	protected $_before_save = [];

	private $_association_data = [];
	private $_attachment_data = [];

	protected $_changed = [];

	public $errors;

	public function __construct(array $data = [])
	{
		parent::__construct([]);

		foreach($data as $k => $v)
			$this->{$k} = $v;

		$this->_changed = [];  // loop above makes model think all fields are dirty..

		$this->errors = new Errors($this);
	}

	public function delete()
	{
		$builder = new SqlBuilder(static::get_table());
		return $builder->delete()->where([$this->get_pk_field() => $this->id()])->execute();
	}

	public function destroy()
	{
		$this->_before_destroy();

		if(FALSE !== ($flag = $this->delete()))
			$this->_after_destroy();

		return $flag;
	}

	public function add_error($error, $field = null)
	{
		$this->errors->add($error, $field);
	}

	public function get_association($property)
	{
		$class = get_called_class();

		if(!isset($this->_association_data[$property])) {
			if(isset($class::$has_many[$property]))
				$association =  new Association\HasMany($this, $property, $class::$has_many[$property]);
			//TODO: Implement other associations

			$this->_association_data[$property] = $association;
		}


		return $this->_association_data[$property];
	}


	public function get_attachment($property)
	{
		$class = get_called_class();

		if(!isset($this->_attachment_data[$property])) {
			if(isset($class::$has_attachment[$property]))
				$attachment =  new Attachment($this, $property, $class::$has_attachment[$property]);

			$this->_attachment_data[$property] = $attachment;
		}


		return $this->_attachment_data[$property];
	}

	public function get_pk_field()
	{
		return static::get_pk();
	}

	public function has_attachments()
	{
		return (bool)count(static::$has_attachment);
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

		if(isset($options['save_attachments']) && !$options['save_attachments'])
			$this->_save_attachments = false;

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

	public function set_association($association_property, array $data)
	{
		return $this->get_association($association_property)->set_data($data);
	}

	public function set_attachment_offset($offset)
	{
		$this->_attachment_offset = $offset;
	}

	public function set_attachment_parent($parent)
	{
		$this->_attachment_parent = $parent;
	}

	public function set_attachment_prop($property)
	{
		$this->_attachment_prop = $property;
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

				$message = 'cannot be blank';
				
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
	protected function _after_destroy()
	{
		foreach(static::$has_attachment as $property => $opts)
			$this->{$property}->delete();

		return true;
	}

	protected function _after_insert()
	{
		return true;
	}

	protected function _after_save()
	{
		$flag = true;

		foreach($this->_after_save as $k => $trig) {
			$this->send($trig);

			unset($this->_after_save[$k]);
		}

		//TODO: Refactor this to a method taht can be cached.  Replace other similar occurences
		$class = \str::from_camel(get_called_class());

		$parent = $this->_attachment_parent;
		$offset = $this->_attachment_offset;
		$has_offset = FALSE !== $offset;
		$base = $parent ? $parent : $class;

		// TODO:  OMG This got bad.. really need to clean it up  (Ability to have nested attachments)
		if(($this->_save_attachments && $this->has_attachments()) && isset($_FILES[$base])) {

			foreach(static::$has_attachment as $prop => $opts) {
				if(!$this->_attachment_prop) {
					if(!isset($_FILES[$base]['name'][$prop]))
						continue;
				} else {
					if(!isset($_FILES[$base]['name'][$this->_attachment_prop]))
						continue;
					elseif($has_offset && (!isset($_FILES[$base]['name'][$this->_attachment_prop][$offset]) || !isset($_FILES[$base]['name'][$this->_attachment_prop][$offset][$prop])))
						continue;
				}

				$attachment = $this->get_attachment($prop);

				$data = [];
				foreach(['name', 'type', 'tmp_name', 'error', 'size'] as $merge) {
					if(!$this->_attachment_prop) {
						if($has_offset) {
							$data[$merge] = $_FILES[$base][$merge][$prop][$offset];
						} else {
							$data[$merge] = $_FILES[$base][$merge][$prop];
						}
					} else {
						if($has_offset) {
							$data[$merge] = $_FILES[$base][$merge][$this->_attachment_prop][$offset][$prop];
						} else {
							$data[$merge] = $_FILES[$base][$merge][$this->_attachment_prop][$prop];
						}
					}
				}

				$attachment->save($data);
			}
		}

		return $flag;
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

	protected function _before_destroy()
	{
		foreach(static::$has_many as $property => $opts) {
			if(isset($opts['destroy']) && $opts['destroy'] == 'cascade') {
				$children = $this->{$property};

				foreach($children as $child)
					$child->destroy();
			}
		}
		foreach(static::$has_one as $property => $opts) {
			if(isset($opts['destroy']) && $opts['destroy'] == 'cascade') {
				$this->{$property}->destroy();
			}
		}
		return true;
	}

	protected function _before_insert()
	{
		return true;
	}

	protected function _before_save()
	{
		//TODO:  Refactor all of these into the same method...
		$flag = true;

		foreach($this->_before_save as $trig)
			$this->send($trig);

		return $flag;
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

	public static function association_exists($property)
	{
		return isset(static::$has_one[$property]) || isset(static::$has_many[$property]) || isset(static::$belongs_to[$property]);
	}

	public static function attachment_exists($property)
	{
		return isset(static::$has_attachment[$property]);
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

	public static function get_attr_type($attr)
	{
		$info = static::get_attr_info($attr);

		return $info->get_type();
	}

	public static function get_attr_info($attr)
	{
		return \P3::database()->get_column_info(static::get_table(), $attr);
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
		if(isset(static::$_table))
			return static::$_table;

		return \str::from_camel(\str::pluralize(get_called_class()));
	}


	public static function get_table_info()
	{
		return \P3::database()->get_table_info(static::get_table());
	}

	public static function get_pk()
	{
		return static::$_pk;
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

	public function __get($property)
	{
		if(static::association_exists($property))
			return $this->get_association($property);
		elseif(static::attachment_exists($property))
			return $this->get_attachment($property);

		return parent::__get($property);
	}

	public function __set($property, $value)
	{
		if(static::association_exists($property) && array_key_exists($property, static::$accept_nexted_attributes_for)) {
			if(!$this->is_new()) {
				$this->_before_save[] = function($record)use($property, $value) { $record->set_association($property, $value); };
				return true;
			} else {
				$this->_after_save[]  = function($record)use($property, $value) { $record->set_association($property, $value); };
				return true;
			}
		}

		return parent::__set($property, $value);
	}
}

?>