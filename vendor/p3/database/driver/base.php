<?php

namespace P3\Database\Driver;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \PDO implements IFace\Driverable
{
	public static $QUERY_CLASS = 'P3\Builder\Sql';

	protected static $_instance;

	protected $_config;

//- Public
	public function __construct(array $config)
	{
		$this->_conf = $config;

		parent::__construct($this->_get_dsn(), $config['username'], $config['password']);
	}

//- Private
	private function _get_dsn()
	{
	}

	public function get_query_class()
	{
		return static::$QUERY_CLASS;
	}

//- Static
	public static function init(array $config)
	{
		if(!isset($config['driver']))
			throw new Exception\NoDriver;

		$class = ucfirct(strtolower($conf['driver'])).'Driver';

		try {
			require_once($class);
			return new $class($config);
		} catch(Exception $e) {
			throw new Exception\UnknownDriver($config['driver']);
		}
	}

	public static function singleton()
	{
		if(!isset(self::$_instance))
			self::$_instance = self::init(P3::config()->database->get_vars());

		return self::$_instance;
	}
}

?>