<?php

namespace P3\Routing;
use       P3\Routing\Route;

/**
 * Description of map
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @todo Fix Accept Format
 */
class Map 
{
	private $_options;
	private $_parent;

//- Public
	public function __construct($parent = null, array $options = [])
	{
		$this->_options = $options;
		$this->_parent  = $parent;
	}

	public function delete($path, array $options = [])
	{
		$options['method'] = 'delete';

		$this->match($path, $options);
	}

	public function get($path, array $options = [])
	{
		$options['method'] = 'get';

		$this->match($path, $options);
	}

	public function match($path, array $options = [])
	{
		$prefix = !isset($options['perform_prefix']) || $options['perform_prefix'];
		$options = array_merge($this->_options, $options);

		if($prefix)
			$path = (strlen($path) && $path[0] == '/') ? $path : $this->_prefix().$path;

		\P3::router()->add(($route = new Route(trim($path, '/'), $options)));

		return $route;
	}

	public function named($name, $path, array $options = [])
	{
		$prefix = !isset($options['perform_prefix']) || $options['perform_prefix'];

		if($prefix)
			$name = $this->_name_base().$name;

		return \url::register_named_route(
				$name, 
				$this->match($path, array_merge(['name' => $name], $options))
		);
	}

	public function namespaced($namespace, $closure)
	{
		$prefix    = $this->_prefix().$namespace;
		$name_base = $this->_name_base().$namespace;
		$namespace = $this->_namespace().$namespace;

		$closure(new self($this, [
			'prefix' => $prefix,
			'name_base' => $name_base,
			'resource' => $namespace,
			'namespace' => $namespace
		]));
	}

	public function post($path, array $options = [])
	{
		$options['method'] = 'post';

		$this->match($path, $options);
	}

	public function put($path, array $options = [])
	{
		$options['method'] = 'put';

		$this->match($path, $options);
	}

	public function resource($resource, array $options = [], $closure = null)
	{
		$plural = \str::pluralize($resource);

		$controller      = isset($options['controller']) ? $options['controller'] : $plural;
		$member_methods  = isset($options['member'])     ? $options['member']     : [];

		$base      = $this->_prefix().$resource;
		$name_base = $this->_name_base().$resource;

		/* closure */
		if(!is_null($closure))
			$closure(new self($this, [
				'prefix' => $base,
				'name_base' => $name_base,
				'resource' => $resource
			]));

		/* new */
		$this->named('new_'.$name_base, $base.'/new', [
			'controller' => $controller, 
			'action' => 'new',
			'method' => 'get',
			'perform_prefix' => false
		]);

		/* edit */
		$this->named('edit_'.$name_base, $base.'/edit', [
			'controller' => $controller, 
			'action' => 'edit',
			'method' => 'get',
			'perform_prefix' => false
		]);

		/* member */
		foreach($member_methods as $action => $method) {
			$this->named($action.'_'.$name_base, $base.'/'.$action, [
				'controller' => $controller, 
				'action' => $action,
				'method' => $method,
				'perform_prefix' => false
			]);
		}

		/* show */
		$this->named($name_base, $base, [
			'controller' => $controller, 
			'action' => 'show',
			'method' => 'get',
			'perform_prefix' => false
		]);

		/* update */
		$this->match($base, [
			'controller' => $controller, 
			'action' => 'update',
			'method' => 'put',
			'perform_prefix' => false
		]);

		/* delete */
		$this->match($base, [
			'controller' => $controller, 
			'action' => 'delete',
			'method' => 'delete',
			'perform_prefix' => false
		]);

		/* create */
		$this->match($base, [
			'controller' => $controller, 
			'action' => 'create',
			'method' => 'post',
			'perform_prefix' => false
		]);
	}

	public function resources($plural_resource, array $options = [], $closure = null)
	{
		$singular = \str::singularize($plural_resource);

		$controller = isset($options['controller']) ? $options['controller'] : $plural_resource;

		$member_methods     = isset($options['member'])     ? $options['member']     : [];
		$collection_methods = isset($options['collection']) ? $options['collection'] : [];

		$base      = $this->_prefix().$plural_resource;
		$name_base = $this->_name_base();
		$sn_base   = $name_base.$singular;
		$pn_base   = $name_base.$plural_resource;

		/* closure */
		if(!is_null($closure))
			$closure(new self($this, [
				'prefix' => $base.'/:'.$singular.'_id', 
				'name_base' => $sn_base,
				'resource' => $singular
			]));

		/* collection */
		foreach($collection_methods as $action => $method) {
			$this->named($action.'_'.$pn_base, $base.'/'.$action, [
				'controller' => $controller, 
				'action' => $action,
				'method' => $method,
				'perform_prefix' => false
			]);
		}

		/* index */
		$this->named($pn_base, $base, [
			'controller' => $controller, 
			'action' => 'index',
			'method' => 'get',
			'perform_prefix' => false
		]);

		/* create */
		$this->match($base, [
			'controller' => $controller, 
			'action' => 'create',
			'method' => 'post',
			'perform_prefix' => false
		]);

		/* new */
		$this->named('new_'.$sn_base, $base.'/new', [
			'controller' => $controller, 
			'action' => 'new',
			'method' => 'get',
			'perform_prefix' => false
		]);

		/* edit */
		$this->named('edit_'.$sn_base, $base.'/:id/edit', [
			'controller' => $controller, 
			'action' => 'edit',
			'method' => 'get',
			'perform_prefix' => false
		]);

		/* member */
		foreach($member_methods as $action => $method) {
			$this->named($action.'_'.$sn_base, $base.'/:id/'.$action, [
				'controller' => $controller, 
				'action' => $action,
				'method' => $method,
				'perform_prefix' => false
			]);
		}

		/* show */
		$this->named($sn_base, $base.'/:id', [
			'controller' => $controller, 
			'action' => 'show',
			'method' => 'get',
			'perform_prefix' => false
		]);

		/* update */
		$this->match($base.'/:id', [
			'controller' => $controller, 
			'action' => 'update',
			'method' => 'put',
			'perform_prefix' => false
		]);

		/* delete */
		$this->match($base.'/:id', [
			'controller' => $controller, 
			'action' => 'delete',
			'method' => 'delete',
			'perform_prefix' => false
		]);
	}

	public function root($options)
	{
		return $this->named('root', '', array_merge(['accept_format' => false], $options));
	}

	public function with_options(array $options, $closure)
	{
		$map = new self($this, array_merge($this->_options, $options));

		return $closure($map);
	}

//- Private
	private function _name_base()
	{
		return isset($this->_options['name_base']) ? $this->_options['name_base'].'_' : '';
	}

	private function _namespace()
	{
		return isset($this->_options['namespace']) ? $this->_options['namespace'].'\\' : '';
	}

	private function _prefix()
	{
		return isset($this->_options['prefix']) ? $this->_options['prefix'].'/' : '';
	}

//- Magic
	public function __call($method, $arguments = array())
	{
		$c = count($arguments);

		switch($c) {
			case 0:
				throw new \P3\Exception\ArgumentException\Invalid(get_class(), 'named', 'No arguments passed');
			case 1:
				return $this->named($method, $arguments[0]);
			case 2:
				return $this->named($method, $arguments[0], $arguments[1]);
			case 3:
				throw new \P3\Exception\ArgumentException\Invalid(get_class(), 'named', 'Too many arguments.  Expecting 1 or 2 args.');
		}
	}
}

?>
