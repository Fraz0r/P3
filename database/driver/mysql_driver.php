<?php

namespace P3\Database\Driver;
use P3\Database\Table\Column;

/**
 * Description of mysql
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class MysqlDriver extends \P3\Database\Driver\Base
{
	public static $DATE_TIME_CLASS = '\P3\Database\Driver\Mysql\DateTime';
	static protected $_table_data = [];

	public function __construct(array $config)
	{
		parent::__construct($config);
		$this->setAttribute(self::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	}

	public function get_table_info($table)
	{
		if(!isset(self::$_table_data[$table])) {
			try{
				$s = $this->query("DESCRIBE `{$table}`");

				$data = [];

				while(FALSE !== ($row = $s->fetch(\PDO::FETCH_ASSOC)))
					$data[$row['Field']] = self::_parse_column($row);

				self::$_table_data[$table] = $data;
			} catch(\PDOException $e) {
				throw new Exception\TableNoExist($table);
			}
		}

		return self::$_table_data[$table];
	}

	protected function _parse_column(array $data)
	{
		$type_vals = explode(' ', $data['Type']);
		$type      = array_shift($type_vals);

		if($type == 'tinyint(1)') {
			$type = Column::TYPE_BOOL;
		} else {
			$paren = strpos($type, '(');

			$type = $paren !== FALSE ? substr($type, 0, $paren) : $type;
		}

		$flags = 0;

		if(count($type_vals))
			foreach($type_vals as $flag)
				if($flag == 'unsigned')
					$flags |= Column::FLAG_UNSIGNED;

		if($data['Null'] == 'NO')
			$flags |= Column::FLAG_NOT_NULL;

		return new Column($data['Field'], $type, $flags);
	}
}

?>