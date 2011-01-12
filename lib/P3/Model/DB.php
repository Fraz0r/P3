<?php
/**
 * **REQUIRES PHP 5.3+ [Late Static Bindings]**
 *
 * Note:  This class Requires a PDO attached.
 *      Call P3_Model_DB::db() with a desired PDO or Extension
 *      prior to using Models.  [Bootstraps a good spot]
 *
 *       Belongs To:  $_belongsTo[accessor]  = (:class => Model, :fk => foreign_key)
 *          Has One:  $_hasOne[accessor]  = (:class => Model, :fk => foreign_key)
 *         Has Many:  $_hasMany[accessor] = (:class => Model, :fk => foreign_key)
 * Has Many Through:  $_hasManyThrough[accessor] = (:class => Model, :joinTable => db_table, :fk => owners_key, :efk => childs_key)
 *
 *
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

	/* Events */
	protected $_beforeCreate  = array();
	protected $_afterCreate   = array();
	protected $_beforeSave    = array();
	protected $_afterSave     = array();
	protected $_beforeDestroy = array();
	protected $_afterDestroy  = array();


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
	 * List of "has many through" relationships
	 *
	 * @var array
	 */
	public static $_hasManyThrough = array();

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
	public function  __construct(array $record_array = null, array $options = array())
	{
		$this->bindEventListeners($options);

		$this->_triggerEvent('beforeCreate');
		parent::__construct($record_array);
		$this->_triggerEvent('afterCreate');
	}

	/**
	 * Adds an EXISTING record model to the current model (via join table)
	 *
	 * Note: This is only for ManyToMany relationships
	 *
	 * @param P3_Model_DB $related_model
	 * @param array $options Options
	 */
	public function addModelToMany($related_model, array $options = array())
	{
		foreach(static::$_hasManyThrough as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == get_class($related_model)) {
				$pk = $this->_data[static::pk()];
				P3_Model_DB::db()->exec("INSERT INTO `{$opts['joinTable']}`({$opts['fk']}, {$opts['efk']}) VALUES('{$pk}', '{$related_model->id}')");
			}
		}
	}

	/**
	 * Binds Listeners to model
	 * @param array $listeners Mult. Dim. Array of closures to be bound [per event]
	 */
	protected function bindEventListeners(array $listeners = array())
	{
		if(isset($listeners['beforeCreate']))
			$this->_beforeCreate = $listeners['beforeCreate'];
		if(isset($listeners['afterCreate']))
			$this->_afterCreate = $listeners['afterCreate'];

		if(isset($listeners['beforeSave']))
			$this->_beforeSave = $listeners['beforeSave'];
		if(isset($listeners['afterSave']))
			$this->_afterSave = $listeners['afterSave'];

		if(isset($listeners['beforeDestroy']))
			$this->_beforeDestroy = $listeners['beforeDestroy'];
		if(isset($listeners['afterDestroy']))
			$this->_afterDestroy = $listeners['afterDestroy'];
	}

	/**
	 * Returns a new relationship model, with fk(s) ready
	 *
	 * Note:  This does NOT work with _belongsTo.  You are doing
	 * shit backwards if you need that functionaliy
	 */
	public function build($model_name, array $record_array = array())
	{
		/* Has One */
		foreach(static::$_hasOne as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = static::pk();
				$fk = $opts['fk'];
				$fields = array_merge($record_array, array($fk => $this->{$pk}));
				return new $class($fields);
			}
		}

		/* Has Many */
		foreach(static::$_hasOne as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = static::pk();
				$fk = $opts['fk'];
				return new $class(array($fk => $this->{$pk}));
			}
		}

		/* Has Many Through */
		foreach(static::$_hasManyThrough as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = $this->_data[self::pk()];

				/* This looks rough, but all it does is binds a save handler to the returning model (To insert the join record upon save) */
				return new $class($record_array, array('afterSave' => array(function($record) use($opts, $pk) {	P3_Model_DB::db()->exec("INSERT INTO `{$opts['joinTable']}`({$opts['fk']}, {$opts['efk']}) VALUES('{$pk}', '{$record->id}')"); })));
			}
		}
	}

	/**
	 * Deletes Record from Database
	 */
	public function delete()
	{
		$pk = static::pk();

		$stmnt = self::db()->prepare('DELETE FROM '.static::$_table.' WHERE '.$pk.' = ?');

		$this->_triggerEvent('beforeDestroy');
		$stmnt->execute(array($this->_data[$pk]));
		$this->_triggerEvent('afterDestroy');

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

		$this->_triggerEvent('beforeSave');
		try {
			if (empty($this->_data[static::pk()]))
				$ret = $this->_insert();
			else
				$ret = $this->_update();
		} catch(PDOException $e) {
			return false;
		}
		$this->_triggerEvent('afterSave');

		return $ret;
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

	protected function _triggerEvent($event)
	{
		$funcs = $this->{'_'.$event};

		if(is_null($funcs))
			throw new P3_Exception("'%s' is not a bindable Event", array($event));

		foreach($funcs as $func)
			$func($this);
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
			} elseif(isset(static::$_hasManyThrough[$name])) {
				$class = static::$_hasManyThrough[$name]['class'];
				$join_table = static::$_hasManyThrough[$name]['joinTable'];
				$fk = static::$_hasManyThrough[$name]['fk'];
				$efk = static::$_hasManyThrough[$name]['efk'];

				$sql = "SELECT b.* FROM `{$join_table}` a";
				$sql .= " INNER JOIN `".$class::$_table."` b ON a.{$efk} = b.".$class::pk();
				$sql .= " WHERE {$fk} = ".$this->_data[static::pk()];

				$stmnt = static::db()->query($sql);
				$stmnt->setFetchMode(PDO::FETCH_CLASS, $class);

				return $stmnt->fetchAll();
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