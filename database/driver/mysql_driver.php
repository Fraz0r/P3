<?php

namespace P3\Database\Driver;

/**
 * Description of mysql
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class MysqlDriver extends Base
{
	public static $QUERY_CLASS = 'P3\Builder\Sql\Mysql';
}

?>