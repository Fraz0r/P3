<?php

namespace P3\Database\Driver\Exception;

/**
 * Description of table_no_exist
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class TableNoExist extends \P3\Exception\DatabaseException
{
	public function __construct($table_name)
	{
		parent::__construct('Table \'%s\' does not exist within database.', array($table_name));
	}
}

?>