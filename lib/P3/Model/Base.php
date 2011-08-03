<?php

namespace P3\Model;

/**
 * Base Model class in P3.  This is extended by P3\ActiveRecord\Base, which should
 * be used by all your models
 * 
 * P3\Model\Base isn't tied to a database in anyway!
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Model
 * @version $Id$
 */
abstract class Base {
//- attr-static-public
	/* Validaters */
	public static $_validatesAlpha    = array();
	public static $_validatesAlphaNum = array();
	public static $_validatesEmail    = array();
	public static $_validatesLength   = array();
	public static $_validatesNum      = array();
	public static $_validatesPresence = array();
	public static $_validatesURL      = array();

//- attr-static-protected
	/**
	 * List of field aliases.  Ex: array("fname" => "first_name")
	 * @var array
	 */
	protected static $_alias = array();

//- attr-protected
	/**
	 * Holds class name for Model, need to switch rest of system off of get_class
	 * @var string
	 */
	protected $_class = null;

	/**
	 * Array to store column data
	 * @var array $_data
	 */
	protected $_data = array();

	protected $_errors  = array();

//- Public
	public function  __construct(array $record_array = null)
	{
		$this->_class = get_class($this);

		if(!is_null($record_array)) {
			foreach($record_array as $k => $v) {
				$this->_data[$k] = $v;
			}
		} 
	}

	/**
	 * Checks to see if passed field has changed since load()
	 * @param str $field Field to check
	 * @return bool
	 */
	public function fieldChanged($field)
	{
		return in_array($field, $this->_changed);
	}

	/**
	 * Returns Fields as associative array
	 */
	public function getData(array $grab = array())
	{
		if(empty($grab))
			return($this->_data);

		$ret = array();

		foreach($grab as $k => $to_send) {
			$key = is_numeric($k) ? ($to_send[0] == ':' ? substr($to_send) : $to_send) : $k;
			$ret[$key] = $this->send($to_send);
		}

		return $ret;
	}

	/**
	 * Returns errors.  If $all is true, returns all errors.  If all is false, only the first error per field is returned.
	 */
	public function getErrors($all = false)
	{
		if($all) {
			return $this->_errors;
		} else {
			$ret = array();
			foreach($this->_errors as $field => $arr) {
				$ret = array_merge($ret, array($arr[0]));
			}


			return $ret;
		}
	}

	public function isNew()
	{
		return true;
	}

	public function pluralize()
	{
		return \str::pluralize(lcfirst($this->_class));
	}

	public function send($what, array $arguments = array())
	{
		if($what[0] == ':')
			return(call_user_func_array(array($this, substr($what, 1)), $arguments));
		else
			return $this->{$what};
	}

	/**
	 * returns Data encoded as JSON
	 */
	public function toJSON(array $fields = array())
	{
		return json_encode($this->getData($fields));
	}

	/**
	 * Update multiple fields of the model in one go
	 *
	 * @param array $values  List of "field => value"'s
	 */
	public function update(array $values)
	{
		foreach($values as $k => $v) {
			$this->{$k} = $v;
		}

		return $this;
	}

	/**
	 * Handles validations
	 *
	 * @return boolean Returns true if all fields are valid
	 */
	public function valid()
	{
		$flag = true;

		/* presence */
		foreach(static::$_validatesPresence as $k => $opts) {
			$field = (!is_array($opts) ? $opts : $k);
			$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s is required';

			if(empty($this->_data[$field])) {
				$flag = false;
				$this->_addError($field, sprintf($msg, \str::toHuman($field)));
			}
		}

		/* email */
		foreach(static::$_validatesEmail as $k => $opts) {
			$field = (!is_array($opts) ? $opts : $k);

			if(empty($this->_data[$field]))
				continue;

			$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s must be a valid email address';

			if(FALSE === filter_var($this->_data[$field], \P3\Filter::FILTER_VALIDATE_EMAIL)) {
				$flag = false;
				$this->_addError($field, sprintf($msg, \str::toHuman($field)));
			}
		}

		/* length (REQUIRES options) */
		foreach(static::$_validatesLength as $k => $opts) {
			if(!is_array($opts) || !count($opts)) {
				throw new Exception\ModelException('_validatesLength requires options', array(), 500);
			}

			$field = $k;

			if(empty($this->_data[$field]))
				continue;

			$min   = isset($opts['min']) ? $opts['min'] : null;
			$max   = isset($opts['max']) ? $opts['max'] : null;

			if(isset($opts['range'])) {
				list($min, $max) = explode('-', $opts['range']);
			}

			if(!is_null($min) && strlen($this->_data[$field]) < $min) {
				$flag = false;
				$this->_addError($field, sprintf('%s must be at least %d characters long', \str::toHuman($field), $min));
			}
			if(!is_null($max) && strlen($this->_data[$field]) > $max) {
				$flag = false;
				$this->_addError($field, sprintf('%s must be less than %d characters long', \str::toHuman($field), $max));
			}
		}

		/* num */
		foreach(static::$_validatesNum as $k => $opts) {
			$field = (!is_array($opts) ? $opts : $k);

			if(empty($this->_data[$field]))
				continue;

			$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s must be numeric';

			if(!preg_match('!^([0-9]*)$!', $this->_data[$field])) {
				$flag = false;
				$this->_addError($field, sprintf($msg, \str::toHuman($field)));
			}
		}

		/* alpha */
		foreach(static::$_validatesAlpha as $k => $opts) {
			$field = (!is_array($opts) ? $opts : $k);

			if(empty($this->_data[$field]))
				continue;

			$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s must contain characters only';

			if(!preg_match('!^([a-zA-Z]*)$!', $this->_data[$field])) {
				$flag = false;
				$this->_addError($field, sprintf($msg, \str::toHuman($field)));
			}
		}

		/* alpha_num */
		foreach(static::$_validatesAlphaNum as $k => $opts) {
			$field = (!is_array($opts) ? $opts : $k);

			if(empty($this->_data[$field]))
				continue;

			$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s must contain characters and numbers only';

			if(!preg_match('!^([a-zA-Z0-9]*)$!', $this->_data[$field])) {
				$flag = false;
				$this->_addError($field, sprintf($msg, \str::toHuman($field)));
			}
		}

		/* url */
		foreach(static::$_validatesURL as $k => $opts) {
			$field = (!is_array($opts) ? $opts : $k);

			if(empty($this->_data[$field]))
				continue;

			$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s must be a valid web address';

			if(!preg_match('/^[a-zA-Z0-9\-\.]+\.(com|org|net|mil|edu)$/i', $this->_data[$field])) {
				$flag = false;
				$this->_addError($field, sprintf($msg, \str::toHuman($field)));
			}
		}

		return $flag;
	}


//- Protected
	/**
	 * Adds error to model
	 *
	 * @param string $field Field error was raised on
	 * @param string $str Error message
	 */
	protected function _addError($field, $str)
	{
		if(!isset($this->_errors[$field]))
			$this->_errors[$field] = array();

		$this->_errors[$field][] = $str;
	}

	protected function _triggerEvent($event)
	{
		$ret   = true;
		$funcs = $this->{'_'.$event};

		if(is_null($funcs))
			throw new Exception\ModelException("'%s' is not a bindable Event", array($event));

		foreach($funcs as $func) {
			if(is_string($func) && $func[0] == ':') {
				$func_name = substr($func, 1);
				$ret = $ret && call_user_func_array(array($this, $func_name), array());
			} elseif(is_callable($func)) {
				$ret = $ret && $func($this);
			} else {
				throw new Exception\ModelException("Unknown event handler type");
			}
		}

		return $ret;
	}

//- Static

//- Magic
	/**
	 * Magic Get:  Retrieve Model Value
	 *
	 * Also handles Relations
	 *
	 * @param string $name accessed db column
	 * @magic
	 */
	public function  __get($name)
	{
		/* Handle Aliases */
		if(!empty(static::$_alias[$name])) {
			$name = static::$_alias[$name];
		}


		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		} else {
			return null;
		}
	}

	/**
	 * Magic Isset: Override isset to include relations
	 * @param string $name Field to check
	 * @return bool True if exists in model, false otherwise
	 */
	public function  __isset($name)
	{
		return(isset($this->_data[$name]));
	}

	/**
	 * Magic Set:  Set a model value
	 *
	 * @param string $name field to set
	 * @param int $value value to set
	 * @magic
	 */
	public function __set($name,  $value)
	{
		if((!method_exists(get_class($this), 'pk') || $name != static::pk()) && isset($this->_data[$name]) && (!is_null($this->_data[$name]) && ($value != $this->_data[$name])))
			$this->_changed[] = $name;

		$this->_data[$name] = $value;
	}

	public function  __toString()
	{
		$str = '#{'.$this->_class.':P3_Model ';
		$attrs = array();
		foreach($this->_data as $k => $v) $attrs[] = "{$k}: {$v}";
		$str .= implode(', ', $attrs).'}';

		return $str;
	}


}

?>