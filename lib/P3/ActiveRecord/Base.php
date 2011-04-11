<?php

namespace P3\ActiveRecord;

/**
 * P3\ActiveRecord\Base
 *
 * P3's Database Model Class
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */
abstract class Base extends \P3\Model\Base
{
//- ATTRIBUTES
	const ATTR_ATTACHMENT_PATH = 1;

	const ATTACHMENT_PATH_PUBLIC = 1;
	const ATTACHMENT_PATH_SYSTEM = 2;

//- attr-public
	/**
	 * Controller to use for model (When generating links)
	 * @var string
	 */
	public $controller = null;

//- attr-protected
	/**
	 * Stores class attributes
	 * @var array
	 */
	protected $_attr = array();

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


//- attr-static
	public static $_validatesUnique   = array();

	/**
	 * List of "belongs to" relationships
	 *
	 * @var array
	 */
	public static $_belongsTo = array();

	/**
	 * List of attachments
	 *
	 * @var array
	 */
	public static $_hasAttachment = array();

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

	/**
	 * Database for models to use
	 *
	 * @var DB
	 */
	public static $_db = null;


	/**
	 * Use get() to fetch an array of models. But if you already have the
	 * array, you can use this __constructer
	 *
	 * @param array $record_array An array of field => val to use for the new model
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
	 * @param P3\ActiveRecord\Base $related_model
	 * @param array $options Options
	 *
	 * @return void
	 */
	public function addModelToMany($related_model, array $options = array())
	{
		foreach(static::$_hasManyThrough as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == get_class($related_model)) {
				$pk = $this->_data[static::pk()];
				if(!$this->isInMany($opts['class'], $related_model->id()))
					static::db()->exec("INSERT INTO `{$opts['joinTable']}`({$opts['fk']}, {$opts['efk']}) VALUES('{$pk}', '{$related_model->id}')");
			}
		}
	}

	/**
	 * Adds error to model
	 *
	 * @param string $str
	 *
	 * @return void
	 */
	public function addError($str)
	{
		$this->_errors[] = $str;
	}

	/**
	 * Binds error to database collumn
	 *
	 * @param string $field Name of Field
	 * @param string $str Error message
	 *
	 * @return void
	 */
	public function addFieldError($field, $str)
	{
		if(!isset($this->_errors[$field])) $this->_errors[$field] = array();

		$this->_errors[$field][] = $str;
	}

	/**
	 * Returns path for given attachment
	 *
	 * @param int $path_type Path type (Public or system)
	 *
	 * @return string Attachment Path
	 */
	public function attachmentPath($attachment, $path_type = null)
	{
		$path_type = is_null($path_type) ? self::ATTACHMENT_PATH_SYSTEM : $path_type;

		$opts = static::$_hasAttachment[$attachment];
		$path = ($path_type == self::ATTACHMENT_PATH_SYSTEM) ? rtrim(\P3\ROOT, '/') : '';
		$path .= rtrim($opts['path'], '/').'/'.$this->id().'/'.$this->_data[$attachment.'_filename'];

		return $path;
	}

	/**
	 * Returns URL for attachment
	 *
	 * @param string $attachment Attachment
	 *
	 * @return string Public URL for attachment
	 */
	public function attachmentURL($attachment)
	{
		return $this->attachmentPath($attachment, self::ATTACHMENT_PATH_PUBLIC);
	}

	/**
	 * Binds Listeners to model
	 *
	 * @param array $listeners Mult. Dim. Array of closures to be bound [per event]
	 *
	 * @return void
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
	 * @param string $model_name Name of model to build
	 * @param array $record_array Array of field/vals for new model
	 *
	 * @return P3\ActiveRecord\Base
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
		foreach(static::$_hasMany as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = static::pk();
				$fk = $opts['fk'];
				$fields = array_merge($record_array, array($fk => $this->{$pk}));
				return new $class($fields);
			}
		}

		/* Has Many Through */
		foreach(static::$_hasManyThrough as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = $this->_data[self::pk()];

				/* This looks rough, but all it does is binds a save handler to the returning model (To insert the join record upon save) */
				return new $class($record_array, array('afterSave' => array(function($record) use($opts, $pk) {	\P3\ActiveRecord\Base::db()->exec("INSERT INTO `{$opts['joinTable']}`({$opts['fk']}, {$opts['efk']}) VALUES('{$pk}', '{$record->id}')"); })));
			}
		}
	}

	/**
	 * Deletes Record from Database
	 *
	 * @return boolean Whether or not the delete was successfull
	 */
	public function delete()
	{
		$pk = static::pk();

		$stmnt = self::db()->prepare('DELETE FROM '.static::$_table.' WHERE '.$pk.' = ?');

		$this->_triggerEvent('beforeDestroy');
		$stmnt->execute(array($this->id()));
		$this->_triggerEvent('afterDestroy');

		$ret = (bool)$stmnt->rowCount();

		if($ret) $this->destroyAttachments();
		return $ret;
	}

	/**
	 * Decrements field in model.  Saves if save => true is passed in options
	 *
	 * @param string $field Field to decrement
	 * @param array $options
	 *
	 * @return int Decremented value
	 */
	public function decrement($field, array $options = array())
	{
		$dec  = isset($options['dec'])  ? $options['dec']  : 1;
		$save = isset($options['save']) ? $options['save'] : false;
		$this->{$field} -= $dec;
		if($save) $this->save();
		return $this->{$field};
	}

	/**
	 * Destroys ALL attachments for model
	 *
	 * @return boolean Whether or not the destroy was successfull
	 */
	public function destroyAttachments()
	{
		$class = $this->_class;

		foreach($class::$_hasAttachment as $accsr => $opts) {
			$dir = \P3\ROOT.'/htdocs'.rtrim($opts['path'], '/').'/'.$this->id();
			if(is_dir($dir)) {
				$objects = scandir($dir);
				foreach($objects as $object) {
					if($object != '.' && $object != '..') {
						unlink("{$dir}/{$object}");
					}
				}
				rmdir($dir);
			}
		}

		return true;
	}

	/**
	 * Returns Primary Key value for the model
	 *
	 * @return int PK Val for Model
	 */
	public function id()
	{
		return $this->{$this->pk()};
	}

	/**
	 * Returns requested attribute
	 *
	 * @param int $attr Attribute flag
	 *
	 * @return mixed Attribute value
	 */
	public function getAttr($attr)
	{
		return isset($this->_attr[$attr]) ? $this->_attr[$attr] : null;
	}

	/**
	 * Returns controller model uses for CRUD.  Attempts to guess if it's not set
	 *
	 * @return string Controller model uses for CRUD
	 */
	public function getController()
	{
		return empty(static::$_controller) ? $this->pluralize() : static::$_controller;
	}

	/**
	 * Returns all, or requested fields as an associative array
	 *
	 * @param array $fields Array of fields requested
	 *
	 * @return array Array of field => val
	 */
	public function getFields(array $fields = array())
	{
		if(empty($fields)) {
			return $this->getData();
		} else {
			$ret = array();
			foreach($this->_data as $field => $val) {
				if(FALSE !== array_search($field, $fields)) {
					$ret[$field] = $val;
				}
			}
			return $ret;
		}
	}

	/**
	 * Increments a field on the model.  Saves if save => true is set in $options.
	 *
	 * @param string $field Field to increment
	 * @param array $options
	 *
	 * @return int Incremented Value
	 */
	public function increment($field, array $options = array())
	{
		$inc  = isset($options['inc'])  ? $options['inc']  : 1;
		$save = isset($options['save']) ? $options['save'] : false;
		$this->{$field} += $inc;
		if($save) $this->save();
		return $this->{$field};
	}

	/**
	 * Determines if model is in Many-Many relationship
	 *
	 * @param mixed $class_or_object
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function isInMany($class_or_object, $id = null)
	{
		if($this->isNew()) return false;

		if(is_object($class_or_object)) {
			$object = $class_or_object;
			$id     = $object->id();
			$class  = get_class($object);

			if($this->isNew()) return false;
		} else {
			$class = $class_or_object;
		}
		if(is_null($id)) return false;

		$flag = false;
		foreach(static::$_hasManyThrough as $accsr => $arr) {
			if($arr['class'] == $class) {
				$join_table = static::$_hasManyThrough[$accsr]['joinTable'];
				$fk = static::$_hasManyThrough[$accsr]['fk'];
				$efk = static::$_hasManyThrough[$accsr]['efk'];

				$sql = "SELECT COUNT(*) FROM `{$join_table}` a";
				$sql .= " INNER JOIN `".$class::$_table."` b ON a.{$efk} = b.".$class::pk();
				$sql .= " WHERE {$fk} = ".$this->_data[static::pk()];
				$sql .= " AND {$efk} = ".$id;
				$sql .= " LIMIT 1";
				$flag = true;
				break;
			}
		}

		if(!$flag) return false;

		$stmnt = static::db()->query($sql);
		return((bool)$stmnt->fetchColumn());
	}

	/**
	 * Returns true if the record is new, False if existing
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return !isset($this->{$this->pk()}) || $this->{$this->pk()} < 0;
	}

	/**
	 * Loads all columns into model (Just pk is needed for this)
	 *
	 * @return void
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
	 * Removes passed model from Many-to-Many relationship
	 *
	 * @param P3\ActiveRecord\Base $related_model
	 *
	 * @return int Number of rows affected
	 */
	public function removeModelFromMany($related_model)
	{
		if(!$this->isInMany($related_model)) return false;

		$class = get_class($related_model);
		foreach(static::$_hasManyThrough as $accsr => $arr) {
			if($arr['class'] == $class) {
				$join_table = static::$_hasManyThrough[$accsr]['joinTable'];
				$fk = static::$_hasManyThrough[$accsr]['fk'];
				$efk = static::$_hasManyThrough[$accsr]['efk'];

				$sql = "DELETE FROM `{$join_table}`";
				$sql .= " WHERE {$fk} = ".$this->_data[static::pk()];
				$sql .= " AND {$efk} = ".$related_model->id();
				$sql .= " LIMIT 1";
				break;
			}
		}

		$stmnt = static::$_db->query($sql);
		return $stmnt->rowCount();
	}

	/**
	 * Saves a record into the database
	 *
	 * @return boolean Whether or not save was successful
	 */
	public function save($options = null)
	{
		if(isset($options['validate']) && $options['validate']) {
			if(count($this->getErrors(true))) return false;
			if(!$this->valid()) return false;
		}

		$save_attachments = (!is_array($options) || !isset($options['save_attachments'])) ? true : $options['save_attachments'];

		$this->_triggerEvent('beforeSave');
		try {
			$this->_parseFields();
			if (empty($this->_data[static::pk()]))
				$ret = $this->_insert();
			else
				$ret = $this->_update();
		} catch(PDOException $e) {
			return false;
		}
		$this->_triggerEvent('afterSave');

		// Handle model attachments
		if($ret && $save_attachments && !empty(static::$_hasAttachment)) {
			$ret = $this->saveAttachments();
		}

		return $ret;
	}

	/**
	 * Saves model's attachments
	 *
	 * @return void
	 */
	public function saveAttachments()
	{
		$ret   = true;
		$class = $this->_class;
		$model_field = \str::fromCamelCase($class);

		foreach(static::$_hasAttachment as $field => $opts) {
			if(isset($_FILES[$model_field])) {
				$data = $_FILES[$model_field];

				switch($data['error'][$field])
				{
					case \UPLOAD_ERR_OK:
						break;
					case \UPLOAD_ERR_NO_FILE:
						break 2;
					default:
						$ret = false;
						$this->_addError($field, 'Upload Error ['.$data['error'][$field].']');
						break;
				}

				$path = \P3\ROOT.'/htdocs'.$opts['path'];

				if(!is_dir($path)) {
					$ret = false;
					throw new \P3\Exception\ActiveRecordException("Attachment directory doesn't exist (%s: %s)", array($class, $path), 500);
				}

				$path .= '/'.$this->id();

				if(!is_dir($path)) mkdir($path);

				$filetype = filetype($data['tmp_name'][$field]);

				//var_dump($data['tmp_name'][$field], $path.'/'.$data['name'][$field]);
				if(!move_uploaded_file($data['tmp_name'][$field], $path.'/'.$data['name'][$field])) {
					$ret = false;
					$this->_addError($field, 'Upload failed');
					break;
				}

				$this->_data[$field.'_filename'] = $data['name'][$field];
				$this->_data[$field.'_filetype'] = $filetype;
				$ret = $this->save(array('save_attachments' => false));
			} 
		}

		return $ret;
	}

	/**
	 * Sets attribute
	 *
	 * @param int $attr
	 * @param mixed $val
	 *
	 * @return void
	 */
	public function setAttribute($attr, $val)
	{
		$this->_attr[$attr] = $val;
	}

	/**
	 * Returns true if record fields are valid
	 *
	 * @return bool
	 */
	public function valid()
	{
		$flag = parent::valid();

		/* unique */
		if($this->isNew()) {
			foreach(static::$_validatesUnique as $k => $opts) {
				$field = (!is_array($opts) ? $opts : $k);
				$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s must be unique';

				$class = $this->_class;
				if(FALSE !== $class::find($field.' = \''.$this->_data[$field].'\'', array('one' => true))) {
					$flag = false;
					$this->_addError($field, sprintf($msg, $field));
				}
			}
		}

		return $flag;
	}

//- Protected
	/**
	 * Saves a new record to the database
	 *
	 * @return boolean
	 */
	protected  function _insert()
	{
		$pk = static::pk();
		$sql = 'INSERT INTO `'.static::$_table.'` ';
		$fields = array();
		$values = array();

		foreach ($this->_data as $f => $v) {
			if ($f == $pk) continue;
			if (in_array($f, static::$_belongsTo) || in_array($f, static::$_hasOne)
					|| in_array($f, static::$_hasMany) || in_array($f, static::$_hasManyThrough))
				continue;

			$fields[] = "`{$f}`";
			$values[] = ":{$f}";
			$ex[":{$f}"] = $v;
		}

		$sql .= '('.implode(', ', $fields).') VALUES('.implode(', ', $values).')';
		$stmnt = static::db()->prepare($sql);
		$stmnt->execute($ex);

		$this->{$pk} = static::db()->lastInsertId();
		return((bool)$stmnt->rowCount());
	}

	/**
	 * Updates a record in the database
	 *
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
			if (in_array($f, array_keys(static::$_belongsTo))
				|| in_array($f, array_keys(static::$_hasOne))
				|| in_array($f, array_keys(static::$_hasMany))
				|| in_array($f, array_keys(static::$_hasManyThrough)))
					continue;

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


//- Static
	/**
	 * Returns all models
	 *
	 * @param array $options
	 *
	 * @return array Array of all models
	 */
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
	 *
	 * @return boolean Whether or not destory was successful
	 */
	public static function destroy($where, array $options = array())
	{
		$skip_int_check = isset($options['skip_int_check']) ? $options['skip_int_check'] : false;


		$sql = 'DELETE FROM '.static::$_table;

		if(!empty($where)) {
			if(!$skip_int_check && is_int($where)) {
				$sql .= ' WHERE '.static::pk().' = '.$where;
				$only_one = true;
			} else {
				$sql .= ' WHERE '.$where;
			}
		}

		return static::db()->exec($sql);
	}

	/**
	 * Find a record, or array of records
	 *
	 * @param string,int $where If $where parses as an int, it's used to check the pk in the table.  Otherwise its places after "WHERE" in the sql query
	 * @param array $options List of options for the query
	 *
	 * @return P3\ActiveRecord\Base
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
		$stmnt->setFetchMode(\PDO::FETCH_CLASS, get_called_class());

		return $only_one ? $stmnt->fetch() : new Collection\Base($stmnt->fetchAll());
	}

	/**
	 * Gets or Sets Database to use for models
	 *
	 * @param P3\Database\Connection $db
	 *
	 * @return mixed Returns Database object if get()
	 */
	public static function db($db = null)
	{
		if(!empty($db)) {
			static::$_db = $db;
		} else {
			if(empty(static::$_db))
				static::$_db = \P3::getDatabase();

			return static::$_db;
		}
	}

	/**
	 * Gets or Sets PK Field for Model
	 *
	 * @param string $pk
	 *
	 * @return mixed Returns pk field if get()
	 */
	public static function pk($pk = null)
	{
		if(!empty($pk)) {
			static::$_pk = $pk;
		} else {
			return static::$_pk;
		}
	}

	/**
	 * Gets or Sets the db table name for the Model
	 *
	 * @param string $table Table to set
	 *
	 * @return mixed Returns database table if get()
	 */
	public static function table($table = null)
	{
		if(!empty($table)) {
			static::$_table = $table;
		} else {
			return static::$_table;
		}
	}

//- Private
	private function _parseFields()
	{
		$tmp = array();

		foreach($this->_data as $k => $v) {
			if(strpos($k, '(')) {
				/* Date / Time */
				if(preg_match('/([^\(]*)\(([\d]){1}i\)/', $k, $m)) {
					if(!isset($tmp[$m[1]])) $tmp[$m[1]] = array();

					$tmp[$m[1]][$m[2]] = $v;
					unset($this->_data[$k]);
				}
			}
		}

		foreach($tmp as $field => $parts) {
			ksort($parts);
			$this->_data[$field] = vsprintf('%04d-%02d-%02d ', $parts);
		}
	}

//- Magic
	/**
	 * Adds relations to isset check
	 *
	 * @param string $name Variable
	 * @magic
	 *
	 * @return boolean True if set, False if not
	 */
	public function  __isset($name)
	{
		if(FALSE !== ($bool = parent::__isset($name))) {
			return $bool;
		} else {
			return(!empty(static::$_hasMany[$name]) || !empty(static::$_hasOne[$name]) || !empty(static::$_belongsTo[$name]));
		}
	}

	/**
	 * Retrievse value from desired field, also handles relations
	 *
	 * @param string $name Field to retrieve
	 * @magic
	 *
	 * @return mixed Value in field
	 */
	public function  __get($name)
	{
		if(null !== ($value = parent::__get($name))) {
			return $value;
		} else {
			$class = null;
			$where = null;
			$opts  = array();

			if(isset(static::$_hasMany[$name])) {
				$class = isset(static::$_hasMany[$name]['class']) ? static::$_hasMany[$name]['class'] : $name;

				if(isset(static::$_hasMany[$name]['sort'])) {
					$opts['order'] = static::$_hasMany[$name]['sort'];
				}
				if(isset(static::$_hasMany[$name]['order'])) {
					$opts['order'] = static::$_hasMany[$name]['order'];
				}

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

				$opts['one'] = true;
			} elseif(isset(static::$_belongsTo[$name])) {
				$class = isset(static::$_belongsTo[$name]['class']) ? static::$_belongsTo[$name]['class'] : $name;

				if(isset(static::$_belongsTo[$name]['fk'])) {
					$where = (int)$this->_data[static::$_belongsTo[$name]['fk']];
				}

				$opts['one'] = true;
			} elseif(isset(static::$_hasManyThrough[$name])) {
				$class = static::$_hasManyThrough[$name]['class'];
				$join_table = static::$_hasManyThrough[$name]['joinTable'];
				$fk = static::$_hasManyThrough[$name]['fk'];
				$efk = static::$_hasManyThrough[$name]['efk'];
				$order = null;

				if(isset(static::$_hasMany[$name]['sort'])) {
					$order = static::$_hasMany[$name]['sort'];
				}
				if(isset(static::$_hasMany[$name]['order'])) {
					$order = static::$_hasMany[$name]['order'];
				}

				$sql = "SELECT b.* FROM `{$join_table}` a";
				$sql .= " INNER JOIN `".$class::$_table."` b ON a.{$efk} = b.".$class::pk();
				$sql .= " WHERE {$fk} = ".$this->_data[static::pk()];
				$sql .= (!empty($order) ? " ORDER BY {$order}" : '');

				$stmnt = static::db()->query($sql);
				$stmnt->setFetchMode(\PDO::FETCH_CLASS, $class);

				return new Collection\Base($stmnt->fetchAll(), $this);
			}
		}

		if($class != null) {
			\P3\Loader::loadModel($class);


			$value = $class::find($where, $opts);
			$this->_data[$name] = $value;

			return $value;
		} else {
			return null;
		}
	}

	/**
	 * Returns an easily distinguishable string for Model
	 *
	 * @return string String representation of model
	 */
	public function __toString()
	{
		return $this->isNew() ?
					'<new>'.$this->_class :
					$this->_class.'<'.$this->id().'>';
	}
}

?>
