<?php

namespace P3\ActiveRecord\Fixture;
use P3\Builder\Sql as SqlBuilder;

/**
 * Description of database
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * 
 */
class Database extends Base
{
	public function write(array $data, $table, $pk = 'id')
	{
		if(isset($data[$pk]))
			return $this->_update($data, $table, $pk);
		else
			return $this->_insert($data, $table, $pk);
	}

//- Protected
	protected function _insert(array $data, $table, $pk)
	{
		$builder = new SqlBuilder($table);

		$success = (bool)$builder->insert($data)->execute();

		return $success ? \P3::database()->lastInsertId() : FALSE;
	}

	protected function _update(array $data, $table, $pk)
	{
		if(!isset($data[$pk]))
			throw new \P3\Exception\ArgumentException\Invalid('Primary Key doesnt exist within passed data');

		$builder = new SqlBuilder($table);
		$builder->update($data)->where([$pk.' = %s', $data[$pk]]);

		return (bool)$builder->execute();
	}
}

?>