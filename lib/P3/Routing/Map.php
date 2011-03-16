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

	public function  __construct($parent = null, $router = null)
	{
		$router = is_null($router) ? \P3::getRouter() : $router;

		$this->_router = $router;
		$this->_parent = $parent;
	}

//public
	public function connect($path, array $options = array())
	{
		$router = $this->_router;
		$route = new Route($path, $options, $this);

		$router::add($route);
		return $route;
	}

	public function named($name, $options)
	{
		return $this;
	}

	public function resource()
	{
		return $this;
	}

	public function resources($controller, array $options = array())
	{
		$prefix = $this->_getPrefix($controller);

		/* Index */
		$index = $this->connect($prefix.'/', array('controller' => $controller, 'action' => 'index'));  //  \url\<model>s

		/* Create */
		$this->connect($prefix.'/', array('controller' => $controller, 'action' => 'create', 'method' => 'post'));  //  \url\models()

		/* New */
		$this->connect($prefix.'/new', array('controller' => $controller, 'action' => 'add'));
		$this->connect($prefix.'/add', array('controller' => $controller, 'action' => 'add'));  //  \url\new_model()

		/* Edit */
		$this->connect($prefix.'/:id/edit', array('controller' => $controller, 'action' => 'edit')); // \url\edit_model(:id)

		/* Show */
		$show = $this->connect($prefix.'/:id', array('controller' => $controller, 'action' => 'show')); // \url\model(:id)

		/* Update */
		$this->connect($prefix.'/:id', array('controller' => $controller, 'action' => 'update', 'method' => 'put'));  //  \url\model(:id)

		/* Delete */
		$this->connect($prefix.'/:id', array('controller' => $controller, 'action' => 'delete', 'method' => 'delete'));  //  \url\model(:id)

		/* Members */
		if(isset($options['member'])) {
			foreach($options['member'] as $member => $method){
				$show->connect('/'.$member, array('action' => $member, 'method' => $method));
			}
		}

		/* Collections */
		if(isset($options['collection'])) {
			foreach($options['collection'] as $collection => $method){
				$index->connect('/'.$collection, array('action' => $collection, 'method' => $method));
			}
		}

		/* Process do "block" */
		if(isset($options['do'])) {
			$func = $options['do'];
			$func($show);
		}

		return $this;
	}

	public function root(array $options = array())
	{
		return $this->connect('/', $options);
	}

	public function withOptions()
	{
		return $this;
	}

//protected
	protected function _getPrefix($controller)
	{
		return '/'.$controller;
	}

//magic
	public function __call($name, $args)
	{
		return $this->named($name, $args);
	}
}
?>