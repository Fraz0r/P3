<?php

namespace P3\Database\Driver;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \PDO implements IFace\Driverable
{
	public static $DATE_TIME_CLASS = '\DateTime';
	public static $QUERY_CLASS = 'P3\Builder\Sql';

	protected $_config;

//- Public
	public function __construct(array $config)
	{
		$this->_config = $config;

		parent::__construct($this->_get_dsn(), $config['username'], $config['password']);
		$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
	}

	public function exec($string)
	{
		$this->log_query($string, 'pdo_exec');

		return parent::exec($string);
	}

	public function get_column_info($table_name, $column)
	{
		$table = static::get_table_info($table_name);

		if(!isset($table[$column]))
			throw new Exception\ColumnNoExist($table_name, $column);

		return $table[$column];
	}


	public function get_date_time_class()
	{
		return static::$DATE_TIME_CLASS;
	}

	public function get_query_class()
	{
		return static::$QUERY_CLASS;
	}

	public function log_query($string, $proc = 'app')
	{
		\P3::logger()->debug("\t{$string}", "p3[{$proc}]");
	}

	public function query($string)
	{
		$this->log_query($string, 'pdo_query');

		return parent::query($string);
	}

//- Private
	private function _get_dsn()
	{
		return sprintf('%s:dbname=%s;host=%s', $this->_config['driver'], $this->_config['database'], $this->_config['host']);
	}

//- Static
	public static function init(array $config)
	{
		if(!isset($config['driver']))
			throw new Exception\NoDriver;

		$class = '\P3\Database\Driver\\'.ucfirst(strtolower($config['driver'])).'Driver';

		try {
			return new $class($config);
		} catch(Exception $e) {
			throw new Exception\UnknownDriver($config['driver']);
		}
	}
}

?>