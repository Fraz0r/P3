<?php
/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\ActiveRecord\Collection;
class Base implements  \IteratorAggregate , \ArrayAccess , \Countable
{
	protected $_contentClass = null;
	protected $_data         = array();
	protected $_parent       = null;
	protected $_parentClass  = null;

	public function __construct($input = null, $parent = null)
	{
		if(!is_array($input)) throw new \P3\Exception\ActiveRecordException("An array must be passed into P3\ActiveRecord\Collection\Base::__construct()");

		$this->_parent = $parent;

		if(!is_null($parent)) $this->_parentClass = \get_class($parent);
		if(count($input)) $this->_contentClass    = \get_class($input[0]);

		$this->_data = $input;

		parent::__construct($input, \ArrayObject::STD_PROP_LIST, '\P3\ActiveRecord\Collection\Iterator');
	}

	public function count()
	{
		return count($this->_data);
	}

	public function filter($closure)
	{
	}

	public function getIterator()
	{
		return new \P3\ActiveRecord\Collection\Iterator($this->_data);
	}

	public function offsetExists($offset) {
		return isset($this->_data[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->_data[] = $value;
		} else {
			$this->_data[$offset] = $value;
		}
	}

	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
	}


}
?>