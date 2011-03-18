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
	public function connect($path, array $options = array(), $method = null)
	{
		$router = $this->_router;
		$route = new Route($path, $options, $method, $this);

		$router::add($route);
		return $route;
	}

	public function named($name, $options)
	{
		return $this;
	}

	public function resource($resource, array $options = array())
	{
		$class = \str::toCamelCase($resource, true);

		$front = isset($options['as']) ? $options['as'] : $resource;
		$controller = $class::$_controller;

		$prefix = isset($options['prefix']) ? $options['prefix'] : '/';

		$prefix .=  $front.'/';

		/* Index */ // I dont think i need/want this?  Ill drink on it
		//$index = $this->connect($prefix.'/', array('controller' => $controller, 'action' => 'index'), 'get');  //  \url\<model>s

		/* Create */
		$this->connect($prefix, array('controller' => $controller, 'action' => 'create'), 'post'); 

		/* New */
		$this->connect($prefix.'new', array('controller' => $controller, 'action' => 'add'), 'get');
		$this->connect($prefix.'add', array('controller' => $controller, 'action' => 'add'), 'get'); 

		/* Edit */
		$this->connect($prefix.'edit', array('controller' => $controller, 'action' => 'edit'), 'get'); 

		/* Show */
		$show = $this->connect($prefix, array('controller' => $controller, 'action' => 'show'), 'get');

		/* Update */
		$this->connect($prefix, array('controller' => $controller, 'action' => 'update'), 'put');

		/* Delete */
		$this->connect($prefix, array('controller' => $controller, 'action' => 'delete'), 'delete');

		return $show;
	}

	public function resources($controller, array $options = array())
	{
		$front = isset($options['as']) ? $options['as'] : $controller;

		$prefix = isset($options['prefix']) ? $options['prefix'] : '/';

		$prefix .=  $front.'/';

		/* Index */
		$index = $this->connect($prefix, array('controller' => $controller, 'action' => 'index'), 'get');  //  \url\<model>s

		/* Create */
		$this->connect($prefix, array('controller' => $controller, 'action' => 'create'), 'post');  //  \url\models()

		/* New */
		$this->connect($prefix.'new', array('controller' => $controller, 'action' => 'add'), 'get');
		$this->connect($prefix.'add', array('controller' => $controller, 'action' => 'add'), 'get');  //  \url\new_model()

		/* Edit */
		$this->connect($prefix.':id/edit', array('controller' => $controller, 'action' => 'edit'), 'get'); // \url\edit_model(:id)

		/* Show */
		$show = $this->connect($prefix.':id', array('controller' => $controller, 'action' => 'show'), 'get'); // \url\model(:id)

		/* Update */
		$this->connect($prefix.'/:id', array('controller' => $controller, 'action' => 'update'), 'put');  //  \url\model(:id)

		/* Delete */
		$this->connect($prefix.'/:id', array('controller' => $controller, 'action' => 'delete'), 'delete');  //  \url\model(:id)

		/* Members */
		if(isset($options['member'])) {
			foreach($options['member'] as $member => $method){
				$show->connect('/'.$member, array('action' => $member), $method);
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

		return $show;
	}

	public function root($options = array())
	{
		if(!is_array($options)) $options = array('to' => $options);

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