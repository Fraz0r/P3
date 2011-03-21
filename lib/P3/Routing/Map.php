<?php

namespace P3\Routing;

/**
 * P3\Routing\Map
 *
 * The Routing Map is used by app/routes.php to load routes into the Routing Engine
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Map
{
//- attr-protected
	/**
	 * Parent Map, if any
	 *
	 * @var P3\Routing\Map
	 */
	protected $_parent = null;

	/**
	 * Router to map Routes to
	 *
	 * @var P3\Router
	 */
	protected $_router = null;

	/**
	 * Options for Map
	 *
	 * @var array
	 */
	protected $_options = array();

//- Public
	public function  __construct($parent = null, $router = null)
	{
		$router = is_null($router) ? \P3::getRouter() : $router;

		$this->_router = $router;
		$this->_parent = $parent;
	}

	/**
	 * Most simple form of binding a route.  Generates a single route and adds
	 * it to the Router
	 *
	 * @param string $path Path to Route
	 * @param array $options Options for Route
	 * @param string $method Method route is good for
	 * @param boolean $accept_format Whether or not the Route should accept a format.  (Same as appeneding [.:format])
	 *
	 * @return Route
	 */
	public function connect($path, array $options = array(), $method = 'any', $accept_format = false)
	{
		if($accept_format) {
			$path = rtrim($path, '/').'[.:format]';
		}

		$router = $this->_router;
		$route = new Route($path, $options, $method, $this);

		$router::add($route);
		return $route;
	}

	/**
	 * Just a stub for now (Coming when URL Generations do)
	 *
	 * @param string $name
	 * @param array $options
	 *
	 * @return Map
	 */
	public function named($name, $options)
	{
	}

	/**
	 * Binds Restful CRUD for a Resource Model
	 *
	 * @param string $resource Resource (model name in underscore format)
	 * @param array $options Options for resource routes
	 *
	 * @return P3\Routing\Route Returns Route for #show
	 */
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
		$this->connect($prefix, array('controller' => $controller, 'action' => 'create'), 'post', true);

		/* New */
		$this->connect($prefix.'new', array('controller' => $controller, 'action' => 'add'), 'get', true);
		$this->connect($prefix.'add', array('controller' => $controller, 'action' => 'add'), 'get', true);

		/* Edit */
		$this->connect($prefix.'edit', array('controller' => $controller, 'action' => 'edit'), 'get', true);

		/* Show */
		$show = $this->connect($prefix, array('controller' => $controller, 'action' => 'show'), 'get', true);

		/* Update */
		$this->connect($prefix, array('controller' => $controller, 'action' => 'update'), 'put', true);

		/* Delete */
		$this->connect($prefix, array('controller' => $controller, 'action' => 'delete'), 'delete', true);

		return $show;
	}

	/**
	 * Binds Restul CRUD for a controller resource
	 *
	 * @param string $controller Controller resource (controller name in underscore form)(w/o _controller)
	 * @param array $options Options for resource routes
	 *
	 * @return P3\Routing\Route Route for #show
	 */
	public function resources($controller, array $options = array())
	{

		$front = isset($options['as']) ? $options['as'] : $controller;

		$prefix = isset($options['prefix']) ? $options['prefix'] : '/';

		$prefix .=  $front.'/';

		/* Index */
		$index = $this->connect($prefix, array('controller' => $controller, 'action' => 'index'), 'get', true);

		/* Create */
		$this->connect($prefix, array('controller' => $controller, 'action' => 'create'), 'post', true);

		/* New */
		$this->connect($prefix.'new', array('controller' => $controller, 'action' => 'add'), 'get', true);
		$this->connect($prefix.'add', array('controller' => $controller, 'action' => 'add'), 'get', true);

		/* Edit */
		$this->connect($prefix.':id/edit', array('controller' => $controller, 'action' => 'edit'), 'get', true);

		/* Show */
		$show = $this->connect($prefix.':id', array('controller' => $controller, 'action' => 'show'), 'get', true);

		/* Update */
		$this->connect($prefix.'/:id', array('controller' => $controller, 'action' => 'update'), 'put', true);

		/* Delete */
		$this->connect($prefix.'/:id', array('controller' => $controller, 'action' => 'delete'), 'delete', true);

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

	/**
	 * Binds and returns root (a.k.a default) route
	 *
	 * @param array $options Options for Route
	 *
	 * @return P3\Routing\Route
	 */
	public function root($options = array())
	{
		if(!is_array($options)) $options = array('to' => $options);

		return $this->connect('/', $options);
	}

	/**
	 * Just a stub for now
	 */
	public function withOptions()
	{
		return $this;
	}

//- Protected
	/**
	 * This will handle namespacing when im done w/ it
	 *
	 * @param string $controller Controller to prefix
	 *
	 * @return string Prefix for Route
	 */
	protected function _getPrefix($controller)
	{
		return '/'.$controller;
	}

//- Magic
	/**
	 * Just a stub for now
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @return P3\Routing\Map
	 */
	public function __call($name, $args)
	{
		return $this->named($name, $args);
	}
}
?>