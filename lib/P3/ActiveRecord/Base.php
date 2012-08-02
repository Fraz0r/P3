<?php

namespace P3\ActiveRecord;
use       P3\Loader;
use       P3\Database\Query\Builder as QueryBuilder;

/**
 * This is the base class for your models to extend.
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\ActiveRecord
 * @version $Id$
 */
abstract class Base extends \P3\Model\Base
{
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
	protected $_beforeUpdate  = array();
	protected $_afterUpdate   = array();
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
	 * List of "many-many" relationships
	 *
	 * @var array
	 */
	public static $_hasAndBelongsToMany = array();

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
	 * Database for models to use
	 *
	 * @var DB
	 */
	public static $_db = null;

	/**
	 * Whether or not the model can be extended
	 * 
	 * @var type bool
	 */
	public static $_extendable = false;

	/**
	 * Scopes for record
	 * 
	 * @var array
	 */
	public static $_scope = array();


	protected static $_queryBuilder = null;


	/**
	 * Use get() to fetch an array of models. But if you already have the
	 * array, you can use this __constructer
	 *
	 * @param array $record_array An array of field => val to use for the new model
	 */
	public function  __construct(array $record_array = null, array $options = array())
	{
		$this->bindEventListeners($options);

		parent::__construct($record_array);

		if(static::$_extendable) {
			$this->type = $this->_class;
		}

		$this->_parseFields();
	}

	/**
	 * Adds an EXISTING record model to the current model (via join table)
	 *
	 * Note: This is only for ManyToMany relationships
	 *
	 * @param P3\ActiveRecord\Base $related_model
	 * @param array $options Options
	 * @return void
	 */
	public function addModelToMany($related_model, array $options = array())
	{
		foreach(static::getHasAndBelongsToMany() as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == get_class($related_model)) {
				$pk = $this->_data[static::pk()];
				if(!$this->isInMany($opts['class'], $related_model->id()))
					static::db()->exec("INSERT INTO `{$opts['table']}`({$opts['fk']}, {$opts['efk']}) VALUES('{$pk}', '{$related_model->id}')");
			}
		}
	}

	/**
	 * Adds error to model
	 *
	 * @param string $str
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
	 * @return P3\ActiveRecord\Base
	 */
	public function build($model_name, array $record_array = array())
	{
		/* Has One */
		foreach(static::getHasOne() as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = static::pk();
				$fk = $opts['fk'];
				$fields = array_merge($record_array, array($fk => $this->{$pk}));
				return new $class($fields);
			}
		}

		/* Has Many */
		foreach(static::getHasMany() as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = static::pk();
				$fk = $opts['fk'];
				$fields = array_merge($record_array, array($fk => $this->{$pk}));
				return new $class($fields);
			}
		}

		/* Has Many Through */
		foreach(static::getHasAndBelongsToMany() as $field => $opts) {
			if(isset($opts['class']) && $opts['class'] == $model_name) {
				$class = $opts['class'];
				$pk = $this->_data[self::pk()];

				/* This looks rough, but all it does is binds a save handler to the returning model (To insert the join record upon save) */
				return new $class($record_array, array('afterSave' => array(function($record) use($opts, $pk) {	\P3\ActiveRecord\Base::db()->exec("INSERT INTO `{$opts['table']}`({$opts['fk']}, {$opts['efk']}) VALUES('{$pk}', '{$record->id}')"); })));
			}
		}
	}

	/**
	 * Returns formatted created_at field
	 * 
	 * @see date()
	 * @param type $format format for date()
	 * @return type string
	 */
	public function createdAt($format = 'n/d/y')
	{
		return date($format, strtotime($this->created_at));
	}

	/**
	 * Deletes Record from Database
	 *
	 * @return boolean Whether or not the delete was successfull
	 */
	public function delete()
	{
		$this->_triggerEvent('beforeDestroy');

		$pk = static::pk();

		$stmnt = self::db()->prepare('DELETE FROM '.static::$_table.' WHERE '.$pk.' = ?');

		$stmnt->execute(array($this->id()));
		$ret = (bool)$stmnt->rowCount();

		if($ret){ 
			$this->_cascade();
			$this->destroyAttachments();
		}

		return $ret && $this->_triggerEvent('afterDestroy');
	}

	/**
	 * Decrements field in model.  Saves if save => true is passed in options
	 *
	 * @param string $field Field to decrement
	 * @param array $options
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
			$attachment = $this->{$accsr};

			if($attachment)
				$attachment->delete();
		}

		return true;
	}

	public function duplicate()
	{
		return clone $this;
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
	 * Returns a subclass of Association\Base, or false on falure
	 * 
	 * @param string $field field association to retrieve
	 * @return Association\Base association for field
	 */
	public function getAssociationForField($field, $ignore_new = false)
	{
		$belongsTo   = static::getBelongsTo();
		$hasMany     = static::getHasMany();
		$hasOne      = static::getHasOne();
		$hasAndBelongsToMany = static::getHasAndBelongsToMany();

		if(isset($belongsTo[$field])) {
			if(isset($belongsTo[$field]['fk']) && empty($this->_data[$belongsTo[$field]['fk']])) {
				return null;
			} elseif(isset($belongsTo[$field]['polymorphic']) && $belongsTo[$field]['polymorphic']) {
				if(!isset($this->_data[$field.'_id']) || !isset($this->_data[$field.'_type']) || in_array(null, array($this->_data[$field.'_id'], $this->_data[$field.'_type'])))
					return null;

				$belongsTo[$field]['polymorphic_as'] = $field;
				return new Association\PolymorphicAssociation($this, $belongsTo[$field]);
			}

			return new Association\BelongsToAssociation($this, $belongsTo[$field]);
		} elseif(isset($hasAndBelongsToMany[$field])) {
			if(!$ignore_new && $this->isNew())
				return false;

			return new Association\HasAndBelongsToMany($this, $hasAndBelongsToMany[$field]);
		} elseif(isset($hasOne[$field])) {
			if(!$ignore_new && $this->isNew())
				return false;

			return new Association\HasOneAssociation($this, $hasOne[$field]);
		} elseif(isset($hasMany[$field])) {
			if(!$ignore_new && $this->isNew())
				return false;

			return new Association\HasManyAssociation($this, $hasMany[$field]);
		} 

		return false;
	}

	/**
	 * Returns attachment object for passed field, or false on failure
	 * 
	 * @param string $field field being accessed
	 * 
	 * @return P3\ActiveRecord\Attachment attachment, or false on failure
	 */
	public function getAttachmentForField($field)
	{
		$attachments = static::getAttachments();

		if(isset($attachments[$field])) {
			$class = isset($attachments[$field]['class']) ? '\\'.$attachments[$field]['class'] : '\P3\ActiveRecord\Attachment';
			return new $class($this, $field, $attachments[$field]);
		} else {
			return false;
		}
	}

	/**
	 * Returns requested attribute
	 *
	 * @param int $attr Attribute flag
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
	 * Determines if model is extendable.  See wiki pages for information.
	 * 
	 * @return boolean whether or not the model is extendable 
	 */
	public function isExtendable()
	{
		return static::$_extendable;
	}

	/**
	 * Determines if model is in Many-Many relationship
	 *
	 * @param mixed $class_or_object
	 * @param integer $id
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
		foreach(static::getHasAndBelongsToMany() as $accsr => $opts) {
			if($opts['class'] == $class) {
				$join_table = $opts['table'];
				$fk = $opts['fk'];
				$efk = $opts['efk'];

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
		$stmnt  = static::db()->query(
					"SELECT *
					 FROM ".static::$_table."
					 WHERE {$pk} = '{$pk_val}'"
		);
		$data = $stmnt->fetch(\PDO::FETCH_ASSOC);
		foreach($data as $k => $v) {
			$this->_data[$k] = $v;
		}
		$stmnt->closeCursor();
	}

	/**
	 * Calls save(), but throws an exception if it returns false
	 * 
	 * @return boolean
	 * 
	 * @see save
	 */
	public function loudSave(array $options = array())
	{
		if(FALSE == ($ret = $this->save($options)))
			throw new \P3\Exception\ActiveRecordException('%s', array(current($this->getErrors())), 500);

		return $ret;
	}

	/**
	 * Reload fields from database back into the model
	 * 
	 * @return type boolean
	 */
	public function reload()
	{
		return $this->load();
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
		foreach(static::getHasAndBelongsToMany() as $accsr => $opts) {
			if($opts['class'] == $class) {
				$join_table = $opts['table'];
				$fk = $opts['fk'];
				$efk = $opts['efk'];

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
		$this->_parseFields();
		$this->_triggerEvent('beforeSave');

		if(!isset($options['validate']) || $options['validate']) {
			if(!$this->valid()) 
					return false;
		}

		$save_attachments = (!is_array($options) || !isset($options['save_attachments'])) ? true : $options['save_attachments'];

		try {
			if (empty($this->_data[static::pk()]))
				$ret = $this->_insert();
			else
				$ret = $this->_update();
		} catch(PDOException $e) {
			return false;
		}

		// Handle model attachments
		if($ret && $save_attachments && !empty(static::$_hasAttachment)) {
			$ret = $this->saveAttachments();
		}

		return $ret && $this->_triggerEvent('afterSave');
	}

	/**
	 * Saves model's attachments
	 *
	 * @return void
	 */
	public function saveAttachments(array $options = array())
	{
		$ret   = true;
		$class = $this->_class;
		$model_field = isset($options['model_field']) ? $options['model_field'] : \str::fromCamelCase($class);

		if(isset($_FILES[$model_field])) {
			foreach(static::$_hasAttachment as $field => $opts) {
				if(isset($_FILES[$model_field]['size'][$field])) {
					$attachment = $this->{$field};

					if($attachment)
						$ret = $ret && $attachment->save($_FILES[$model_field]);
				}
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

	public function toCSV()
	{
		if(!isset(static::$_comma))
			throw new \P3\Exception\ActiveRecordException('You must set a public static::$_comma with the fields you want to render in csv format.');

		$fields = array();
		foreach(static::$_comma as $k => $v)
			$fields[] = is_numeric($k) ? $v : $k;

		$line = array();
		foreach($fields as $field)
			$line[] = '"'.$this->send($field).'"';

		return implode(',', $line);
	}

	public function toCSVHeader()
	{
		if(!isset(static::$_comma))
			throw new \P3\Exception\ActiveRecordException('You must set a public static::$_comma with the fields you want to render in csv format.');

		$header = array_values(static::$_comma);

		foreach($header as &$h)
			$h = '"'.$h.'"';

		return implode(',', $header);
	}

	public function update_attribute($attr, $val)
	{
		$this->{$attr} = $val;

		return $this->save(array('validate' => false));
	}

	public function update_attributes($attrs)
	{
		foreach($attrs as $attr => $val)
			$this->{$attr} = $val;

		return $this->save(array('validate' => false));
	}

	/**
	 * Update fields and save record
	 * 
	 * @param array $fields
	 * @return boolean true for success, false for failure
	 */
	public function updateAndSave(array $fields)
	{
		$this->update($fields);
		return $this->save();
	}

	/**
	 * Returns formatted updated_at field
	 * 
	 * @see date()
	 * @param string $format format for date
	 * @return string formatted date/time
	 */
	public function updatedAt($format = 'n/d/y')
	{
		return date($format, strtotime($this->updated_at));
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
		foreach(static::$_validatesUnique as $k => $opts) {
			$field = (!is_array($opts) ? $opts : $k);
			$msg   = is_array($opts) && isset($opts['msg']) ? $opts['msg'] : '%s must be unique';

			$class = $this->_class;
			$func_opts = array();

			$search = static::all(array('conditions' => array('email' => $this->_data[$field])));
			if(!$this->isNew())
				$search->getBuilder()->where('id != '.$this->id(), \P3\Database\Query\Builder::MODE_APPEND);


			if(count($search)) {
				$flag = false;
				$this->_addError($field, sprintf($msg, $field));
			}
		}

		return !(bool)count($this->_errors);
	}

//- Protected
	/**
	 * Saves a new record to the database
	 *
	 * @return boolean
	 */
	protected function _insert()
	{
		$ret = $this->_triggerEvent('beforeCreate');

		if(!$ret)
			return false;

		$this->created_at = date("Y-m-d H:i:s", time());
		$this->updated_at = date("Y-m-d H:i:s", time());

		$pk = static::pk();
		$sql = 'INSERT INTO `'.static::$_table.'` ';
		$fields = array();
		$values = array();

		foreach ($this->_data as $f => $v) {
			if ($f == $pk) continue;
			if (array_key_exists($f, static::getBelongsTo()) || array_key_exists($f, static::getHasOne())
					|| array_key_exists($f, static::getHasMany()) || array_key_exists($f, static::getHasAndBelongsToMany()))
				continue;

			$fields[] = "`{$f}`";
			$values[] = ":{$f}";
			$ex[":{$f}"] = $v;
		}

		$sql .= '('.implode(', ', $fields).') VALUES('.implode(', ', $values).')';
		$stmnt = static::db()->prepare($sql);
		$stmnt->execute($ex);

		$this->{$pk} = static::db()->lastInsertId();


		$success = (bool)$stmnt->rowCount();

		if($success)
			$this->_changed = array();

		return $success && $this->_triggerEvent('afterCreate');
	}

	/**
	 * Get or Set Query Builder for the ActiveRecord
	 * 
	 * @param P3\Query\Builder $builder builder to set, get() mode if null
	 * @return mixed void if set(), P3\Query\Builder if get()
	 */
	protected static function _queryBuilder($builder = null)
	{
		if($builder == null) {
			if(static::$_queryBuilder == null)
				static::$_queryBuilder = new QueryBuilder(static::$_table);

			return static::$_queryBuilder;
		} else {
			static::$_queryBuilder = $builder;
		}
	}

	/**
	 * Updates a record in the database
	 *
	 * @return boolean
	 */
	protected  function _update()
	{
		if(!$this->_triggerEvent('beforeUpdate'))
			return false;

		$this->updated_at = date("Y-m-d H:i:s", time());

		$pk = static::pk();
		$sql = 'UPDATE '.static::$_table.' SET ';
		$fields = array();
		$values = array();

		foreach ($this->_data as $f => $v) {
			if ($f == $pk) continue; // We don't update the value of the pk
			if (array_key_exists($f, static::getBelongsTo()) || array_key_exists($f, static::getHasOne())
					|| array_key_exists($f, static::getHasMany()) || array_key_exists($f, static::getHasAndBelongsToMany()))
				continue;

			$fields[] = $f.' = ?';
			$values[] = $v;
		}

		$sql .= implode(',', $fields);
		$sql .= ' WHERE '.$pk.' = ?';
		$values[] = $this->_data[$pk];
		$stmnt = static::db()->prepare($sql);
		$stmnt->execute($values);

		$success = (($stmnt->rowCount() === false)? false : true);

		if($success)
			$this->_changed = array();

		return $success && $this->_triggerEvent('afterUpdate');
	}


//- Static
	/**
	 * Returns all models.  
	 * 
	 * Options:
	 * 	order:  Clause for `ORDER BY` (dont include "ORDER BY" when you set this)
	 * 	one:    Set true if you want only one model, and not a collection
	 * 	limit:  number of records to pull
	 *
	 * @param array $options
	 * @return array Array of all models
	 */
	public static function all(array $options = array())
	{
		$order    = (isset($options['order']) && !is_null($options['order'])) ? $options['order'] : static::pk().' ASC';
		$only_one = isset($options['one']) ? $options['one'] : false;
		$limit    = isset($options['limit']) ? $options['limit'] : null;
		$flags    = 0;
		$class    = get_called_class();

		if($only_one) {
			$limit = 1;
			$flags = $flags | Collection\FLAG_SINGLE_MODE;
		}

		if(static::$_extendable)
			$flags = $flags | Collection\FLAG_DYNAMIC_TYPES;

		$builder = new QueryBuilder(static::$_table, null, $class);

		$builder->select();

		if(!isset($options['conditions'])) {
			$builder->where('1');
		} else {
			foreach($options['conditions'] as $k => $v) {
				if(!is_numeric($k) && !is_array($v))
					$builder->where($k.'=\''.$v.'\'', QueryBuilder::MODE_APPEND);
				else {
					$builder->where($v, QueryBuilder::MODE_APPEND);
				}
			}
		}

		if(static::$_extendable) {
			$parent_class = array_shift(class_parents($class));

			if($parent_class !== __CLASS__)
				$builder->where('type = \''.$class.'\'', QueryBuilder::MODE_APPEND);
		} 

		$builder->order($order);

		if(!is_null($limit)) {
			if(!is_array($limit))
				$offset = null;
			else
				list($limit, $offset) = $limit;

			$builder->limit($limit, $offset);
		}

		$collection = new Collection\Base($builder, null, $flags);
		$collection->contentClass($class);


		return $only_one ? $collection->first() : $collection;
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
	 * Options:
	 * 	conditions:  Additional conditions for WHERE clause
	 *
	 * @param $id id of record to retrieve
	 * @param array $options list of options for the query
	 * @return P3\ActiveRecord\Base
	 */
	public static function find($id, array $options = array())
	{
		$order    = (isset($options['order']) && !is_null($options['order'])) ? $options['order'] : static::pk().' ASC';
		$limit    = isset($options['limit']) ? $options['limit'] : null;
		$flags    = 0;
		$class    = get_called_class();

		$only_one = !is_array($id);

		if($only_one) {
			$limit = 1;
			$flags = $flags | Collection\FLAG_SINGLE_MODE;
		}

		if(static::$_extendable)
			$flags = $flags | Collection\FLAG_DYNAMIC_TYPES;

		$builder = new QueryBuilder(static::$_table, null, $class);

		$builder->select();

		if(!is_array($id)) {
			$builder->where(static::pk().' = '.$id);
		} else {
			$builder->where(static::pk().' IN ('.implode(', ', $id).')');
		}

		if(isset($options['conditions'])) {
			foreach($options['conditions'] as $k => $v) {
				if(!is_numeric($k) && !is_array($v))
					$builder->where($k.'=\''.$v.'\'', QueryBuilder::MODE_APPEND);
				else {
					$builder->where($v, QueryBuilder::MODE_APPEND);
				}
			}
		}

		if(static::$_extendable) {
			$parent_class = array_shift(class_parents($class));

			if($parent_class !== __CLASS__)
				$builder->where('type = \''.$class.'\'', QueryBuilder::MODE_APPEND);
		} 

		$builder->order($order);

		if(!is_null($limit)) {
			if(!is_array($limit))
				$offset = null;
			else
				list($limit, $offset) = $limit;

			$builder->limit($limit, $offset);
		}

		$collection = new Collection\Base($builder, null, $flags);


		return $only_one ? $collection->first() : $collection;
	}

	public static function getAttachments()
	{
		return static::getMergedProp('_hasAttachment');
	}

	/**
	 * Get Merged belongsTo.  See Model Extensions on wiki pages;
	 * 
	 * @return array
	 */
	public static function getBelongsTo()
	{
		return static::getMergedProp('_belongsTo');
	}

	/**
	 * Get Merged getHasAndBelongsToMany.  See Model Extensions on wiki pages;
	 * 
	 * @return array
	 */
	public static function getHasAndBelongsToMany()
	{
		return static::getMergedProp('_hasAndBelongsToMany');
	}

	/**
	 * Get Merged getHasOne.  See Model Extensions on wiki pages;
	 * 
	 * @return array
	 */
	public static function getHasOne()
	{
		return static::getMergedProp('_hasOne');
	}

	/**
	 * Get Merged HasMany.  See Model Extensions on wiki pages;
	 * 
	 * @return array
	 */
	public static function getHasMany()
	{
		return static::getMergedProp('_hasMany');
	}

	/**
	 * Get Merged static Property. See Model Extensions on wii pages
	 * 
	 * @return array
	 */
	public static function getMergedProp($prop)
	{
		if(static::$_extendable) {
			$parents = class_parents(get_called_class());
			$ret = static::${$prop};
			foreach($parents as $parent) {
				if($parent == __CLASS__)
					break;

				$ret = array_merge($ret, $parent::${$prop});
			}

			return $ret;
		} else {
			return static::${$prop};
		}
	}

	/**
	 * Gets or Sets Database to use for models
	 *
	 * @param P3\Database\Connection $db
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
	 * @return mixed Returns database table if get()
	 */
	public static function table($table = null)
	{
		if(!empty($table)) {
			static::$_table = $table;
		} else {
			if(is_null(static::$_table))
				static::$_table = \str::pluralize(\str::fromCamelCase(get_called_class()));

			return static::$_table;
		}
	}

	/**
	 * Starts a transaction, and then runs the passed closure
	 * 
	 * If the passed closure throws ANY kind of exception, the transaction
	 * is rolled back.  Otherewise, COMMIT will be called.
	 * 
	 * Remember, save() does NOT throw an exception - it only returns false
	 * 
	 * @param type $closure closure containing queries
	 */
	public static function transaction($closure)
	{
		$_db = static::db();
		$_db->beginTransaction();
		try {
			$ret = $closure();
			$_db->commit();
		} catch(\Exception $e) {
			$_db->rollBack();

			throw $e;
		}

		return $ret;
	}

//- Private
	/**
	 * Cascades delete action to children (Nullify or Delete).  This is only called if the 'cascade'
	 * option is set in the association
	 * 
	 * @return void
	 */
	private function _cascade()
	{
		$cascadeables = array_merge(static::getHasMany(), static::getHasOne(), static::getBelongsTo());

		foreach($cascadeables as $accessor => $opts) {
			if(isset($opts['dependent']) && !\is_null($opts['dependent'])) {

				if(!in_array($opts['dependent'], array('destroy', 'delete', 'nullify')))
					throw new \P3\Exception\ActiveRecordException("Unknown option passed for 'dependent' in the association for %s->%s", array($this->_class, $accessor));

				if(isset($opts['through'])) 
					throw new \P3\Exception\ActiveRecordException("Cannot %s children accross a 'through' relationship for %s->%s.  Move this dependent option to the other side of the join.", array($opts['dependent'], $this->_class, $accessor));

				$assoc = $this->__get($accessor);

				if(!$assoc)
					continue;

				/* If it's not a collection, it's a single model - so put it in array to use same loop below and not dupe logic */
				if(!is_subclass_of($assoc, 'P3\ActiveRecord\Collection\Base'))
					$assoc = array($assoc);

				foreach($assoc as $child) {
					switch($opts['dependent']) {
						case 'destroy':
						case 'delete':
							$child->delete();
							break;
						case 'nullify':
							$child->{$opts['fk']} = null;
							$child->save(array('validate' => false));
							break;
					}
				}
			}
		}
	}

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
			/* If a "part" was left off, ignore the field all together */
			if(count($parts) != count(array_filter($parts)))
				continue;

			ksort($parts);
			$this->_data[$field] = vsprintf('%04d-%02d-%02d ', $parts);
		}
	}

//- Magic
	public static function __callStatic($name, $arguments) 
	{
		if(substr($name, 0, 8) == 'find_by_') {
			$all   = false;
			$field = substr($name, 8);
		} if(substr($name, 0, 12) == 'find_all_by_') {
			$all   = true;
			$field = substr($name, 12);
		}

		if(isset($field)) {
			if(!$all)
				$arguments['one'] = true;


			$field = static::table().'.'.$field;
			$args = array();
			$args['conditions'] = array($field => array_shift($arguments));


			foreach($arguments as $key => $arg) {
				if(is_array($arg)) {
					foreach($arg as $k => $v)
						if($k == 'conditions')
							$args['conditions'] = array_merge($args['conditions'], is_array($v) ? $v : array($v));
						else
							$args[$k] = $v;
				} else {
					$args[$key] = $arg;
				}
			}

			return self::all($args);
		} else {
			if(isset(static::$_scope[$name])) {
				$args = isset($arguments[0]) && is_array($arguments[0]) ? $arguments[0] : array();

				return self::all(array_merge(static::$_scope[$name], $args));
			}
		}

		throw new \P3\Exception\ActiveRecordException("Method doesnt exist: %s", array($name));
	}

	public function __clone()
	{
		unset($this->_data[static::pk()]);
	}

	/**
	 * Retrievse value from desired field, also handles relations
	 *
	 * @param string $name Field to retrieve
	 * @return mixed Value in field
	 * @magic
	 */
	public function  __get($name)
	{
		if(null !== ($value = parent::__get($name))) {
			return $value;
		} elseif(FALSE !== ($attachment = $this->getAttachmentForField($name))) {
			return $attachment;
		} else {
			$assoc = $this->getAssociationForField($name);

			if(!$assoc) 
				return null;


			$ret =  $assoc->inSingleMode() ? $assoc->first() : $assoc;

			if(FALSE !== $ret)
				$this->_data[$name] = $ret;


			return $ret;
		}

		return null;
	}

	/**
	 * Adds relations to isset check
	 *
	 * @param string $name Variable
	 * @return boolean True if set, False if not
	 * @magic
	 */
	public function  __isset($name)
	{
		return parent::__isset($name);
	}

	public function __set($name, $val)
	{
		if($assoc = $this->getAssociationForField($name, true)) {
			$assoc->equals($val);
		} else {
			parent::__set($name, $val);
		}
	}

	/**
	 * Returns an easily distinguishable string for Model
	 *
	 * @return string String representation of model
	 * @magic
	 */
	public function __toString()
	{
		return $this->isNew() ?
					'<new>'.$this->_class :
					$this->_class.'<'.$this->id().'>';
	}
}

?>
