<?php

class P3_DB extends PDO
{
	public function __construct(array $config, array $options = array())
	{
		/* Build our DSN if it's not in the config */
		$dsn  = isset($config['dsn']) ? $config['dsn'] : $this->buildDSN($config);

		$user = empty($config['username']) ? null : $config['username'];
		$pass = empty($config['password']) ? null : $config['password'];

		parent::__construct($dsn, $user, $pass);
		$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
	}

	public function buildDSN(array $config)
	{
		return $config['driver'].':'.'host='.$config['host'].((!empty($config['port'])) ? (';port='.$config['port']) : '').';dbname='.$config['database'];
	}


	/**
	 *
	 * @param string $table SQL DB Table
	 * @param string $where SQL Where Statment
	 */
	public function destroy($table, $where = null)
	{
		if($where != null) {
			$s = $this->query('DELETE FROM '.$table.' WHERE '.$where);
		}
	}

	/**
	 * Get one or more matching Models
	 *
	 * @param string $class Model Name to cast into
	 * @param string,int $where Assumed PK if integer, where clause if string
	 * @param string $what defaults to '*' [The columns you want]
	 */
	public function get ($class, $where='1', $force_single = false, $order_by = null)
	{
		P3_Loader::loadModel($class);

		/* A little user checking never hurts... checking if user probably meant to send an (int)where */
		if($where !== '1' && (int)$where > 0) {
			$where = (int)$where;
		}

		/* Get Table */
		$model = new $class();
		$table = $model->getTable();

		/* Build Query Statement */
		$sql = 'SELECT * FROM '.$table.' WHERE ';
		if(is_int($where)) {
			$sql .= $model->_pk.'=\''.$where.'\'';
		} else {
			$sql .= $where;
		}

		if($order_by !== null) {
			$sql .= ' ORDER BY '.$order_by;
		}

		/* Run our statement, and initialize our return Array [of models] */
		$stmnt = $this->query($sql);
		$ret   = array();

		while($r = $stmnt->fetch(PDO::FETCH_ASSOC)) {
			$ret[] = new $class($r);
			if($force_single) {
				break;
			}
		}

		if((bool)count($ret)) {
			return((!is_int($where) && !$force_single) ? $ret : $ret[0]);
		} else {
			return null;
		}
	}

	/**
	 * Returns (Y-m-d H:i:s)[yyyy-mm-dd hh:mm:ss] of Passed timestamp
	 *
	 * @param int $timestamp Unix Timestamp
	 * @return string DateTime Format
	 */
	public static function timestampToDateTime($timestamp = null)
	{
		if(is_null($timestamp))
			$timestamp = time();

		return date("Y-m-d H:i:s", $timestamp);
	}

	public static function U ($table, array $fields, $pk = 'id')
	{
		$pk_val = $fields[$pk];
		unset($fields[$pk]);
		$values = array();
		foreach ($fields as $k => $v) {
			$values[] = $k.'=?';
		}

		$sql = 'UPDATE '.$table.' SET '.implode(', ',$values).' WHERE '.$pk.' = '.$pk_val;
		return $sql;
	}
}

?>
