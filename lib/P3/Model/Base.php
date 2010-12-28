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
	protected $_alias = array();

	/**
	 * List of "has many" relationships
	 *
	 * @var array
	 */
	public $_has_many = array();

	/**
	 * List of "has one" relationships
	 *
	 * @var array
	 */
	public $_has_one = array();

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
		$in_data  = (!empty($this->_data[$name])     ? true : false);
		$in_one   = (!empty($this->_has_one[$name])  ? true : false);
		$in_many  = (!empty($this->_has_many[$name]) ? true : false);

		return($in_data || $in_one || $in_many);
	}

	/**
	 * Magic Get:  Retrieve Model Value
	 *
	 * Also handles Relations
	 *
	 * @param string $name accessed db column
	 * @magic
	 *
	 * @todo Relations need to be moved out of the db class, otherwise a Base model will not work
	 */
	public function  __get($name)
	{
		/* Handle Aliases */
		if(!empty($this->_alias[$name])) {
			$name = $this->_alias[$name];
		}

		/* If key exists in db row */
		if(isset($this->_data[$name])) {
			/* Check if this key is mapped to another class via _belongs_to */
			if(isset($this->_belongs_to) && array_key_exists($name, $this->_belongs_to)) {
				$owner = $this->_belongs_to[$name];
				return self::$_db->get($owner, (int)$this->_data[$name]);
			}
			else{
				/* If unmapped, just return the var */
				return($this->_data[$name]);
			}
		} elseif(isset($this->_has_one[$name])) {
			$assignment = $this->_has_one[$name];
			$child = $assignment[0];
			$field = $assignment[1];
			return(self::$_db->get($child, "{$field} = '{$this->_data[$this->_pk]}'", true));
		} elseif(isset($this->_has_many[$name])) {
			$assignment = $this->_has_many[$name];
			$child = $assignment[0];
			$field = $assignment[1];
			return(self::$_db->get($child, "{$field} = '{$this->_data[$this->_pk]}'"));
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
		if($name != $this->pk && (!isset($this->_data[$name]) || ($value != $this->_data[$name])))
			$this->_changed[] = $name;

		$this->_data[$name] = $value;
	}


}

?>