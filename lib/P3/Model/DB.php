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
	 * The PK of the table.  Override this if other than 'id'
	 * @var string
	 */
	public $_pk = 'id';

	/**
	 * Stores changed columns [for update]
	 * @var array
	 */
	protected $_changed = array();

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
		$pk  = $this->_pk;
		$sql = 'DELETE FROM '.$this->_table.' WHERE '.$pk.' = \''.$this->_data[$pk].'\'';
		return self::$_db->query($sql);
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
	 * Retrieve the db table name
	 *
	 */
	public function getTable ()
	{
		return $this->_table;
	}

}

?>