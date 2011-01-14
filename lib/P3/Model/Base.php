<?php
/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

class P3_Model_Base {
	/**
	 * List of field aliases.  Ex: array("fname" => "first_name")
	 *
	 * @var array
	 */
	protected static $_alias = array();

	/**
	 * Array to store column data
	 * @var array $_data
	 */
	protected $_data = array();

	public function  __construct(array $record_array = null)
	{
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
	 * Returns Fields as array
	 */
	public function getData()
	{
		return($this->_data);
	}

	/**
	 * returns Data encoded as JSON
	 */
	public function toJSON()
	{
		return json_encode($this->_data);
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


// Protected
	protected function _triggerEvent($event)
	{
		$funcs = $this->{'_'.$event};

		if(is_null($funcs))
			throw new P3_Exception("'%s' is not a bindable Event", array($event));

		foreach($funcs as $func)
			$func($this);
	}

// Static

// Magic
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


		if (isset($this->{$name})) {
			return $this->_data[$name];
		} else {
			return null;
		}
	}

	/**
	 * Magic Set:  Set a model value
	 *
	 * @param string $name field to set
	 * @param int $value value to set
	 * @magic
	 */
	public function  __set($name,  $value)
	{
		if($name != static::pk() && isset($this->_data[$name]) && (!is_null($this->_data[$name]) || ($value != $this->_data[$name])))
			$this->_changed[] = $name;

		$this->_data[$name] = $value;
	}



}

?>