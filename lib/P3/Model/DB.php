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

abstract class P3_Model_DB extends P3_Model_Base
{
//Public
	/**
	 * Controller to use for model (When generating links)
	 * @var string
	 */
	public $controller = null;

//Protected
	/**
	 * Stores changed columns [for update]
	 * @var array
	 */
	protected $_changed = array();


//Static
	/**
	 * List of "belongs to" relationships
	 *
	 * @var array
	 */
	public static $_belongsTo = array();

	/**
	 * List of "has many" relationships
	 *
	 * @var array
	 */
	public static $_hasMany = array();

	/**
	 * List of "has one" relationships
	 *
	 * @var array
	 */
	public static $_hasOne = array();

	/**
	 * Class name to lower (Database Table)
	 * @var string
	 */
	public static $_table;

	/**
	 * The PK of the table.  Override this if other than 'id'
	 * @var string
	 */
	public static $_pk = 'id';

	/**
	 * List of collumns in the db table
	 *
	 * @var array
	 */
	public static $_dbColumns = array();

	public static $_db = null;


	/**
	 * Use get() to fetch an array of models. But if you already have the array, you can use this __constructer
	 *
	 * @param array $record_array an array of field => val
	 */
	public function  __construct(array $record_array = null)
	{
		parent::__construct($record_array);
	}

	/**
	 * Sets up the db for use across all models   (This is usually done in the bootstrap)
	 * @param P3_DB $db DB singleton
	 */
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
		$pk = static::pk();

		$stmnt = $this->_db->prepare('DELETE FROM '.static::$_table.' WHERE '.$pk.' = ?');
		$stmnt->execute(array($this->_data[$pk]));
		return((bool)$stmnt->rowCount());
	}

	public function decrement($field, array $options = array())
	{
		$dec  = isset($options['dec'])  ? $options['dec']  : 1;
		$save = isset($options['save']) ? $options['save'] : false;
		$this->{$field} -= $dec;
		if($save) $this->save();
		return $this->{$field};
	}

	public function increment($field, array $options = array())
	{
		$inc  = isset($options['inc'])  ? $options['inc']  : 1;
		$save = isset($options['save']) ? $options['save'] : false;
		$this->{$field} += $inc;
		if($save) $this->save();
		return $this->{$field};
	}

	/**
	 * Returns true if the record is new, False if existing
	 * @return bool
	 */
	public function isNew()
	{
		return $this->{$this->pk()} < 0;
	}

	/**
	 * Loads all columns into model (Just pk is needed for this)
	 */
	public function load()
	{
		$pk     = static::pk();
		$pk_val = $this->_data[$pk];
		$stmnt  = self::$_db->query(
					"SELECT *
					 FROM ".static::$_table."
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
		if(!$this->valid()) {
			return false;
		}

		if (empty($this->_data[static::pk()])) {
			return($this->_insert());
		} else {
			return($this->_update());
		}
	}

	/**
	 * Returns true if record fields are valid for saving
	 * Note:  Not yet implemented
	 * @return bool
	 */
	public function valid()
	{
		return true;
	}

//Protected
	/**
	 * Saves a new record to the database
	 * @return boolean
	 */
	protected  function _insert()
	{
		$pk = static::pk();
		$sql = 'INSERT INTO '.static::$_table.' ';
		$fields = array();
		$values = array();

		foreach ($this->_data as $f => $v) {
			if ($f == $pk) continue;
			$fields[] = $f;
			$values[] = "'$v'";
		}

		$sql .= '('.implode(',', $fields).') VALUES('.implode(',', $values).')';
		$stmnt = static::db()->prepare($sql);
		$stmnt->execute($values);
		$this->{$pk} = static::db()->lastInsertId();
		return((bool)$stmnt->rowCount());
	}

	/**
	 * Updates a record in the database
	 * @return boolean
	 */
	protected  function _update()
	{
		$pk = static::pk();
		$sql = 'UPDATE '.static::$_table.' SET ';
		$fields = array();
		$values = array();

		foreach ($this->_data as $f => $v) {
			if ($f == $pk) continue; // We don't update the value of the pk
			$fields[] = $f.' = ?';
			$values[] = $v;
		}

		$sql .= implode(',', $fields);
		$sql .= ' WHERE '.$pk.' = ?';
		$values[] = $this->_data[$pk];
		$stmnt = static::db()->prepare($sql);
		$stmnt->execute($values);
		return(($stmnt->rowCount() === false)? false : true);
	}


//Static
	public static function all(array $options = array())
	{
		$options['skip_int_check'] = true;
		return static::find('1', $options);
	}

	/**
	 * Destroy records matching $where
	 *
	 * @param string,int $where If $where parses as an int, it's used to check the pk in the table.  Otherwise its places after "WHERE" in the sql query
	 * @param array $options List of options for the query
	 */
	public static function destroy($where, array $options = array())
	{
		$skip_int_check = isset($options['skip_int_check']) ? $options['skip_int_check'] : false;


		$sql = 'DELETE '.static::$_table;

		if(!empty($where)) {
			if(!$skip_int_check && is_int($where)) {
				$sql .= ' WHERE '.static::pk().' = '.$where;
				$only_one = true;
			} else {
				$sql .= ' WHERE '.$where;
			}
		}
	}

	/**
	 * Find a record, or array of records
	 *
	 * @param string,int $where If $where parses as an int, it's used to check the pk in the table.  Otherwise its places after "WHERE" in the sql query
	 * @param array $options List of options for the query
	 */
	public static function find($where, array $options = array())
	{
		$order    = (isset($options['order']) && !is_null($options['order'])) ? $options['order'] : static::pk().' ASC';
		$only_one = isset($options['one']) ? $options['one'] : false;
		$limit    = isset($options['limit']) ? $options['limit'] : null;
		$skip_int_check = isset($options['skip_int_check']) ? $options['skip_int_check'] : false;

		if($only_one)
			$limit = 1;


		$sql = 'SELECT * FROM '.static::$_table;

		if(!empty($where)) {
			if(!$skip_int_check && is_int($where)) {
				$sql .= ' WHERE '.static::pk().' = '.$where;
				$only_one = true;
			} else {
				$sql .= ' WHERE '.$where;
			}
		}

		$sql .= ' ORDER BY '.$order;

		if(!is_null($limit)) {
			if(!is_array($limit)) {
				$sql .= ' LIMIT '.$limit;
			} else {
				$sql .= ' LIMIT '.$limit[0].', '.$limit[1];
			}
		}

		$stmnt = static::db()->query($sql);
		$stmnt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

		return $only_one ? $stmnt->fetch() : $stmnt->fetchAll();
	}

	public static function db($db = null)
	{
		if(!empty($db)) {
			static::$_db = $db;
		} else {
			return static::$_db;
		}
	}

	public static function pk($pk = null)
	{
		if(!empty($pk)) {
			static::$_pk = $pk;
		} else {
			return static::$_pk;
		}
	}

	/**
	 * Retrieve the db table name
	 *
	 */
	public static function table($table = null)
	{
		if(!empty($table)) {
			static::$_table = $table;
		} else {
			return static::$_table;
		}
	}

//Magic
	public function  __isset($name)
	{
		if(FALSE !== ($bool = parent::__isset($name))) {
			return $bool;
		} else {
			return(!empty(static::$_hasMany[$name]) || !empty(static::$_hasOne[$name]) || !empty(static::$_belongsTo[$name]));
		}
	}

	public function  __get($name)
	{
		if(null !== ($value = parent::__get($name))) {
			return $value;
		} else {
			$class = null;
			$where = null;
			$one   = false;

			if(isset(static::$_hasMany[$name])) {
				$class = isset(static::$_hasMany[$name]['class']) ? static::$_hasMany[$name]['class'] : $name;

				if(isset(static::$_hasMany[$name]['fk'])) {
					$where = static::$_hasMany[$name]['fk'].'='.$this->_data[static::pk()];
				} else {
					$where = strtolower(get_called_class()).'_id ='.$this->_data[static::pk()];
				}
			} elseif(isset(static::$_hasOne[$name])) {
				$class = isset(static::$_hasOne[$name]['class']) ? static::$_hasOne[$name]['class'] : $name;

				if(isset(static::$_hasOne[$name]['fk'])) {
					$where = static::$_hasOne[$name]['fk'].'='.$this->_data[static::pk()];
				} else {
					$where = strtolower(get_called_class()).'_id ='.$this->_data[static::pk()];
				}

				$one = true;
			} elseif(isset(static::$_belongsTo[$name])) {
				$class = isset(static::$_belongsTo[$name]['class']) ? static::$_belongsTo[$name]['class'] : $name;

				if(isset(static::$_belongsTo[$name]['fk'])) {
					$where = (int)$this->_data[static::$_belongsTo[$name]['fk']];
				}

				$one = true;
			}
		}

		if($class != null) {
			P3_Loader::loadModel($class);


			$value = $class::find($where, array("one" => $one));
			$this->_data[$name] = $value;

			return $value;
		} else {
			return null;
		}
	}
}

?>