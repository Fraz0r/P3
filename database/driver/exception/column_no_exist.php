<?php

namespace P3\Database\Driver\Exception;


/**
 * Description of column_no_exist
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class ColumnNoExist extends \P3\Exception\DatabaseException
{
	public function __construct($table, $column)
	{
		parent::__construct('Column \'%s\' does not exist within \'%s\'', array($column, $table));
	}
}

?>