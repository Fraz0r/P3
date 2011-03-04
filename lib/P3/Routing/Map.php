<?php
/**
 * Description of Map
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\Routing;

class Map
{
//protected

	/**
	 * @var P3\Routing\Map
	 */
	protected $_parent = null;

	/**
	 * @var P3\Router
	 */
	protected $_router = null;

	/**
	 * @var array
	 */
	protected $_options = array();

//public
	public function connect()
	{
		return $this;
	}

	public function named($name, $options)
	{
		return $this;
	}

	public function resource()
	{
		return $this;
	}

	public function resources()
	{
		return $this;
	}

	public function root()
	{
		return $this;
	}

	public function withOptions()
	{
		return $this;
	}

//protected
	protected function _mapREST($controller)
	{
	}

//magic
	public function __call($name, $args)
	{
		return $this->named($name, $args);
	}
}
?>