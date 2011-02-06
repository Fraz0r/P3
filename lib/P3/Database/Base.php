<?php

namespace P3\Database;

class Base extends \PDO
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
		return $config['driver'].':'.'host='.(isset($config['host']) ? $config['host'] : 'localhost').((!empty($config['port'])) ? (';port='.$config['port']) : '').';dbname='.$config['database'];
	}


}

?>
