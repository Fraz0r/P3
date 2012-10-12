<?php

namespace P3\Database\Driver;

/**
 * Description of mysql
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class MysqlDriver extends \P3\Database\Driver\Base
{
	public function __construct(array $config)
	{
		parent::__construct($config);
		$this->setAttribute(self::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	}

	public function get_table_info($table)
	{
	}
}

?>