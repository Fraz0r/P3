<?php

namespace P3\ActiveRecord;
use P3\Builder\Sql as SqlBuilder;

/**
 * Description of collection
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Collection  extends \P3\Object\Collection
{
	const STATE_STARTED = 1;
	const STATE_FILLED  = 2;

	private $_builder;
	private $_count;
	private $_state = 0;
	private $_statement;

	public function __construct(SqlBuilder $builder, $fetch_class = null)
	{
		$this->_builder = $builder;

		if(!is_null($fetch_class))
			$this->_builder->fetch_class($fetch_class);
	}

	public function average($what)
	{
		if($this->is_filled())
			return parent::average($what);

		$builder = clone $this->_builder;
		$builder->select("AVG({$what}) as avg", SqlBuilder::MODE_OVERRIDE);

		$result = $builder->send();

		return $result->fetchColumn();
	}

	public function builder($builder = null)
	{
		return is_null($builder) ? $this->get_builder() : $this->set_builder($builder);
	}

	public function count()
	{
		if($this->is_filled())
			return parent::count();

		if(!isset($this->_count))
			$this->_count = $this->_builder->fetch_count();

		return $this->_count;
	}

	public function fetch()
	{
		if(!$this->is_started())
			$this->_start();

		//TODO:  Add dynamic flag
		$this->_statement->setFetchMode(\PDO::FETCH_ASSOC);

		$record = $this->_statement->fetch();

		if($record && !is_null(($class = $this->_builder->fetch_class())))
			$record = new $class($record);

		$this->_data[$this->_pointer++] = $record;

		if($record === FALSE)
			$this->_state = $this->_state ^ self::STATE_FILLED;

		return $record;
	}

	public function get_builder()
	{
		return $this->_builder;
	}

	public function is_filled()
	{
		if(!$this->is_started())
			return false;

		return (bool)$this->_state & self::STATE_FILLED;
	}

	public function is_started()
	{
		return (bool)$this->_state & self::STATE_STARTED;
	}
	
	public function limit($limit, $offset = null)
	{
		$this->reset();

		$this->_builder->limit($limit, $offset);

		return $this;
	}

	public function max($what)
	{
		if($this->is_filled())
			return parent::average($what);

		$builder = clone $this->_builder;
		$builder->select("MAX({$what}) as max", SqlBuilder::MODE_OVERRIDE);

		$result = $builder->send();

		return $result->fetchColumn();
	}

	public function min($what)
	{
		if($this->is_filled())
			return parent::average($what);

		$builder = clone $this->_builder;
		$builder->select("MIN({$what}) as min", SqlBuilder::MODE_OVERRIDE);

		$result = $builder->send();

		return $result->fetchColumn();
	}

	public function reset()
	{
		$this->_data    = [];
		$this->_pointer = 0;
		$this->_count   = null;
		$this->_state   = 0;
		$this->_statement = null;
	}

	public function set_builder(SqlBuilder $builder)
	{
		$this->_builder = $builder;

		return $this;
	}

	public function where($what, $mode = SqlBuilder::MODE_APPEND)
	{
		$this->reset();

		$this->_builder->where($what, $mode);

		return $this;
	}

//- Private
	private function _start()
	{
		$this->_state = $this->_state ^ self::STATE_STARTED;
		$this->_statement = $this->_builder->send();
	}
}

?>