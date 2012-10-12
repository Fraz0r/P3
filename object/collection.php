<?php

namespace P3\Object;

/**
 * Description of collection
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Collection implements \Iterator, \ArrayAccess, \Countable
{
	private $_data;
	private $_pointer = 0;

	public function __construct(array $items = array())
	{
		$this->_data = $items;
	}

//- Public
	public function add($item)
	{
		return $this->push($item);
	}

	public function avg($what)
	{
		return $this->average($what);
	}

	public function average($what)
	{
		$tmp = $this->collect($what)->export();

		if(count($tmp) == 0)
			return 0;

		return array_sum($tmp) / count($tmp);
	}

	public function collect($what)
	{
		$ret = new self;

		foreach($this as $item)
			if(is_array($item))
				$ret->add($item[$what]);
			elseif($item instanceof \P3\Object)
				$ret->add($item->send($what));
			else
				$ret->add($item->{$what});

		return $ret;
	}

	public function current()
	{
		return $this->_data[$this->_pointer];
	}

	public function count()
	{
		return count($this->_data);
	}

	public function export()
	{
		return $this->_data;
	}

	public function key()
	{
		return $this->_pointer;
	}

	public function next()
	{
		return ++$this->_pointer;
	}

	public function max($what)
	{
		return max($this->collect($what)->export());
	}

	public function min($what)
	{
		return min($this->collect($what)->export());
	}

	public function offsetExists($offset)
	{
		return isset($this->_data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->_data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->_data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
	}

	public function pop()
	{
		return array_pop($this->_data);
	}

	public function push($item)
	{
		return array_push($this->_data, $item);
	}

	public function remove_if($closure)
	{
		$ret = clone $this;

		foreach($ret as $k => $item)
			if($closure($item))
				unset($ret[$k]);

		return $ret;
	}

	public function rewind()
	{
		$this->_pointer = 0;
	}

	public function select($closure)
	{
		$ret = new self;

		foreach($this as $item)
			if($closure($item))
				$ret->add($item);

		return $ret;
	}

	public function shift()
	{
		return array_shift($this->_data);
	}

	public function unshift($item)
	{
		return array_unshift($this->_data, $item);
	}

	public function valid()
	{
		return isset($this->_data[$this->_pointer]);
	}
}

?>