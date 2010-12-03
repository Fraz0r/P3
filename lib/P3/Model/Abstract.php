<?php
/**
 * **REQUIRES PHP 5.3+ [Late Static Bindings]**
 *
 * Note:  This class Requires a PDO attached.
 *      Call EE_Model_Abstract::setDB or
 *      setDatabase with a desired PDO or Extension
 *      prior to using Models.  [Bootstraps a good spot]
 *
 *       Belongs To:  $_belongs_to[field]  = Model
 *          Has One:  $_has_one[accesser]  = (Model, foreign_key)
 *         Has Many:  $_has_many[accesser] = (Model, foreign_key)
 * Has Many Through:  Incoming...
 *
 *
 * @todo   Have delete() cascade through [relating] models (If wanted, of course)(Figuring out a way to work constraints)
 * @author Tim Frazier <tim.frazier@gmail.com>
 */

abstract class P3_Model_Abstract
{

	private static $_db;

	/**
	 * The PK of the table.  Override this if other than 'id'
	 * @var string
	 */
	public $_pk = 'id';

	protected $_alias = array();

	/**
	 * Stores changed columns [for update]
	 * @var array
	 */
	protected $_changed = array();

	public $_has_one = array();
	public $_has_many = array();

	/**
	 * Array to store column data
	 * @var array $_data
	 */
	protected $_data = array();

	/**
	 * Class name to lower (Database Table)
	 * @var string
	 */
	public $_table;

	/**
	 * Use get() to fetch an array of models. But if you already have the array, you can use this __constructer
	 *
	 * @param array $record_array an array of field => val
	 */
	public function  __construct(array $record_array = null)
	{
		if(!is_null($record_array)) {
			foreach($record_array as $k => $v) {
				$this->_data[$k] = $v;
			}
			if(empty($record_array[$this->_pk])) {
				$this->{$this->_pk} = -1;
			}

		} else {
			$this->{$this->_pk} = -1;
		}
	}

	public static function setDB(P3_DB $db)
	{
		self::$_db = $db;
	}

	/**
	 * Deletes Record from Database
	 *
	 */
	public function delete()
	{
		$pk  = $this->_pk;
		$sql = 'DELETE FROM '.$this->_table.' WHERE '.$pk.' = \''.$this->_data[$pk].'\'';
		return self::$_db->query($sql);
	}


	/**
	 * Magic Get:  Retrieve DB Column
	 *
	 * Also handles Relations
	 *
	 * @param string $name accessed db column
	 * @magic
	 */
	public function  __get($name)
	{
		/* Handle Aliases */
		if(!empty($this->_alias[$name])) {
			$name = $this->_alias[$name];
		}

		/**
		 * @todo fix magic get
		 */

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

	public function  __isset($name)
	{
		$in_data  = (!empty($this->_data[$name])     ? true : false);
		$in_one   = (!empty($this->_has_one[$name])  ? true : false);
		$in_many  = (!empty($this->_has_many[$name]) ? true : false);

		return($in_data || $in_one || $in_many);
	}

	/**
	 * Returns DB Collumns as array
	 */
	public function getData()
	{
		return($this->_data);
	}

	/**
	 * Loads all columns into model (Just pk is needed for this)
	 */
	public function load()
	{
		$pk     = $this->_pk;
		$pk_val = $this->_data[$pk];
		$stmnt  = self::$_db->query(
					"SELECT *
					 FROM {$this->_table}
					 WHERE {$pk} = '{$pk_val}'"
		);
		$data = $stmnt->fetch(PDO::FETCH_ASSOC);
		foreach($data as $k => $v) {
			$this->_data[$k] = $v;
		}
		$stmnt->closeCursor();
	}

	/**
	 * Saves a record into the database
	 */
	public function save()
	{
		$arr    = $this->_data;
		$pk_val = $this->_data[$this->_pk];
		unset($arr[$this->_pk]);

		if($pk_val != -1) {
			if((bool)count($this->_changed)) {
				$sql = 'UPDATE '.$this->_table.' SET ';
				foreach(array_unique($this->_changed) as $v) {
					$keys[] = "{$v}=?";
					$vals[] = $this->_data[$v];
				}
				$sql .= implode(', ', $keys);
				$sql .= ' WHERE '.$this->_pk.' = \''.$pk_val.'\'';
				$stmnt = self::$_db->prepare($sql);
				$stmnt->execute($vals);
			}
		} else {
			$sql  = 'INSERT INTO '.$this->_table.'('.implode(', ',array_keys($arr)).')';
			foreach($arr as $k => $v) {
				$vals[] = "?";
				$sets[] = "{$k}='".addslashes($v)."'";
			}
			$sql .= ' VALUES('.implode(', ',$vals).')';
			/* Handle duplicate key (Update) */
			$sql .= ' ON DUPLICATE KEY UPDATE '.implode(', ', $sets);
			$stmnt = self::$_db->prepare($sql);
			$stmnt->execute(array_values($arr));
			$this->_data[$this->_pk] = self::$_db->lastInsertId();

			/* Load all keys (This will grab mysql defaults) */
			$this->load();
		}
	}

	/**
	 * Magic Set:  Set a db column value
	 *
	 * @param string $name db column to set
	 * @param int $value value to set it
	 */
	public function  __set($name,  $value)
	{
		if($name != $this->pk && (!isset($this->_data[$name]) || ($value != $this->_data[$name])))
			$this->_changed[] = $name;

		$this->_data[$name] = $value;
	}

	public function set(array $values)
	{
		foreach($values as $k => $v) {
			$this->{$k} = $v;
		}
	}

	/**
	 * returns Data encoded as JSON
	 */
	public function toJSON()
	{
		return json_encode($this->_data);
	}
	/**
	 * Retrieve the db table name
	 *
	 */
	public function getTable ()
	{
		return $this->_table;
	}

}

?>