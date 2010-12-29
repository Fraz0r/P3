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
	/**
	 * Stores changed columns [for update]
	 * @var array
	 */
	protected $_changed = array();

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


	/**
	 * Use get() to fetch an array of models. But if you already have the array, you can use this __constructer
	 *
	 * @param array $record_array an array of field => val
	 */
	public function  __construct(array $record_array = null)
	{
		parent::__construct($record_array);

		if(is_null($this->_data[$this->_pk]))
			$this->_data[$this->_pk] = -1;
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
		$sql = 'DELETE FROM '.static::$_table.' WHERE '.static::$_pk.' = \''.$this->_data[static::$_pk].'\'';
		return self::$_db->query($sql);
	}

	/**
	 * Loads all columns into model (Just pk is needed for this)
	 */
	public function load()
	{
		$pk     = self::$_pk;
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
		$arr    = $this->_data;
		$pk_val = $this->_data[static::$_pk];
		unset($arr[static::$_pk]);

		if($pk_val != -1) {
			if((bool)count($this->_changed)) {
				$sql = 'UPDATE '.static::$_table.' SET ';
				foreach(array_unique($this->_changed) as $v) {
					$keys[] = "{$v}=?";
					$vals[] = $this->_data[$v];
				}
				$sql .= implode(', ', $keys);
				$sql .= ' WHERE '.static::$_pk.' = \''.$pk_val.'\'';
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

//Static
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

		if($only_one)
			$limit = 1;


		$sql = 'SELECT * FROM '.static::$_table;

		if(!empty($where)) {
			if(is_int($where)) {
				$sql .= ' WHERE '.static::pk().' = '.$where;
			} else {
				$sql .= ' WHERE '.$where;
			}
		}

		$sql .= ' ORDER BY '.$order;

		if(!is_null($limit)) {
			if(!is_array($limit)) {
				$sql .= 'LIMIT '.$limit;
			} else {
				$sql .= 'LIMIT '.$limit[0].', '.$limit[1];
			}
		}

		$stmnt = static::db()->query($sql);
		$stmnt->setFetchMode(P3_DB::FETCH_CLASS, get_called_class());

		return $only_one ? $stmnt->fetch() : $stmnt->fetchAll();
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
				}
			} elseif(isset(static::$_hasOne[$name])) {
				$class = isset(static::$_hasOne[$name]['class']) ? static::$_hasOne[$name]['class'] : $name;

				if(isset(static::$_hasOne[$name]['fk'])) {
					$where = static::$_hasOne[$name]['fk'].'='.$this->_data[static::pk()];
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

		P3_Loader::loadModel($class);
		$value = $class::find($where, array("one" => $one));
		$this->_data[$name] = $value;

		return $value;
	}
}

?>