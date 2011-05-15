<?php

namespace P3\Database;

/**
 * This is an extension of PDO used by P3 to communicate with your database.
 * (configuraable in ./config/database.ini)
 * 
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\Database
 * @version $Id$
 */
class Connection extends \PDO
{
	/**
	 * Creates new instance of PDO
	 *
	 * @param array $config Config array for connection
	 * @param array $options Options
	 */
	public function __construct(array $config = array(), array $options = array())
	{
		if(empty($config)) {
			$file = new \P3\Config\Parser;
			$file->read(array(\P3\ROOT.'/config/database.ini'));
			$config = $file->getSection(\P3::getEnv());
		}

		/* Build our DSN if it's not in the config */
		$dsn  = isset($config['dsn']) ? $config['dsn'] : $this->buildDSN($config);

		$user = empty($config['username']) ? null : $config['username'];
		$pass = empty($config['password']) ? null : $config['password'];

		parent::__construct($dsn, $user, $pass);
		$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
	}

	/**
	 * Builds PDO DSN with passed config array
	 *
	 * Uses: driver, host, port, & database
	 *
	 * @param array $config Config array containing fields for DSN
	 * @return string PDO DSN
	 */
	public function buildDSN(array $config)
	{
		return $config['driver'].':'.'host='.(isset($config['host']) ? $config['host'] : 'localhost').((!empty($config['port'])) ? (';port='.$config['port']) : '').';dbname='.$config['database'];
	}


}

?>
