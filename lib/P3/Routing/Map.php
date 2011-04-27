<?php

namespace P3\Routing;
use       P3\Routed\Route;

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
	public function  __construct($parent = null, $router = null, array $options = array())
	{
		$router = is_null($router) ? \P3::getRouter() : $router;

		$this->_router  = $router;
		$this->_parent  = $parent;
		$this->_options = $options;
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
	public function connect($path, array $options = array(), $method = 'any', $accept_format = true)
	{
		$prefix = isset($options['prefix']) ? $options['prefix'] : $this->_getPrefix($path);

		if(!empty($this->_options))
				$options = array_merge($this->_options, $options);

		if($accept_format)
			$path = rtrim($path, '/').'[.:format]';

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

	public function namespaced($namespace)
	{
		return $this->withOptions(array('namespace' => $namespace));
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
		if(!empty($this->_options))
				$options = array_merge($this->_options, $options);

		$class = \str::toCamelCase($resource, true);

		$front = isset($options['as']) ? $options['as'] : $resource;
		$controller = $class::$_controller;

		/* Index */ // I dont think i need/want this?  Ill drink on it
		//$index = $this->connect($prefix.'/', array('controller' => $controller, 'action' => 'index'), 'get');  //  \url\<model>s

		/* Create */
		$this->connect($front.'/', array('controller' => $controller, 'action' => 'create'), 'post', true);

		/* New */
		$this->connect($front.'/new', array_merge($options, array('controller' => $controller, 'action' => 'add')), 'get', true);
		$this->connect($front.'/add', array_merge($options, array('controller' => $controller, 'action' => 'add')), 'get', true);

		/* Edit */
		$this->connect($front.'/edit', array_merge($options, array('controller' => $controller, 'action' => 'edit')), 'get', true);

		/* Show */
		$show = $this->connect($front.'/', array_merge($options, array('controller' => $controller, 'action' => 'show')), 'get', true);

		/* Update */
		$this->connect($front.'/', array_merge($options, array('controller' => $controller, 'action' => 'update')), 'put', true);

		/* Delete */
		$this->connect($front.'/', array_merge($options, array('controller' => $controller, 'action' => 'delete')), 'delete', true);

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
		if(!empty($this->_options))
				$options = array_merge($this->_options, $options);

		$only_set = isset($options['only']);

		if($only_set)
			$only = $options['only'];


		$front = isset($options['as']) ? $options['as'] : $controller;

		/* Index */
		if(!$only_set || in_array('index', $only))
			$index = $this->connect($front.'/', array_merge($options, array('controller' => $controller, 'action' => 'index')), 'get', true);

		/* Create */
		if(!$only_set || in_array('create', $only))
			$this->connect($front.'/', array_merge($options, array('controller' => $controller, 'action' => 'create')), 'post', true);

		/* New */
		if(!$only_set || in_array('add', $only) || in_array('new', $only)) {
			$this->connect($front.'/new', array_merge($options, array('controller' => $controller, 'action' => 'add')), 'get', true);
			$this->connect($front.'/add', array_merge($options, array('controller' => $controller, 'action' => 'add')), 'get', true);
		}

		/* Edit */
		if(!$only_set || in_array('edit', $only))
			$this->connect($front.'/:id/edit', array_merge($options, array('controller' => $controller, 'action' => 'edit')), 'get', true);

		/* Show */
		if(!$only_set || in_array('show', $only))
			$show = $this->connect($front.'/:id', array_merge($options, array('controller' => $controller, 'action' => 'show')), 'get', true);

		/* Update */
		if(!$only_set || in_array('update', $only))
			$this->connect($front.'/:id', array_merge($options, array('controller' => $controller, 'action' => 'update')), 'put', true);

		/* Delete */
		if(!$only_set || in_array('delete', $only))
			$this->connect($front.'/:id', array_merge($options, array('controller' => $controller, 'action' => 'delete')), 'delete', true);

		/* Members */
		if(isset($show) && isset($options['member'])) {
			foreach($options['member'] as $member => $method){
				$show->connect('/'.$member, array('action' => $member), $method);
			}
		}

		/* Collections */
		if(isset($index) && isset($options['collection'])) {
			foreach($options['collection'] as $collection => $method){
				$index->connect('/'.$collection, array('action' => $collection, 'method' => $method));
			}
		}

		/* Process do "block" */
		if(isset($options['do'])) {
			$func = $options['do'];
			$func($show);
		}

		return isset($show) ? $show : null;
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
		$prefix = $this->_getPrefix('');

		if(!is_array($options)) $options = array('to' => $options);

		if(!empty($this->_options))
				$options = array_merge($this->_options, $options);

		return $this->connect('/', $options, 'get', false);
	}

	/**
	 * Just a stub for now
	 */
	public function withOptions(array $options = array(), $closure = null)
	{
		if(!empty($this->_options))
				$options = array_merge($this->_options, $options);

		$map = new self($this, $this->_router, $options);

		if(!is_null($closure))
			$closure($map);

		return $map;
	}

//- Protected
	/**
	 * This will handle namespacing when im done w/ it
	 *
	 * @param string $controller Controller to prefix
	 *
	 * @return string Prefix for Route
	 */
	protected function _getPrefix($path)
	{
		$path = ltrim($path, '/');
		$ret = '/';

		if(isset($this->_options['namespace'])) {
			$ret .= $this->_options['namespace'].'/';
		}

		$ret .= $path;

		return $ret;
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
