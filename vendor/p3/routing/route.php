<?php

namespace P3\Routing;
use       P3\Routing\Route\Segment;
use       P3\Net\Http\Request;

/**
 * Description of route
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Route implements iFace\Segmentable
{
	private $_options;
	private $_params = array();
	private $_path;
	private $_raw;
	private $_regex;
	private $_segments;

	public function __construct($path, $options)
	{
		$this->_path    = $path;
		$this->_options = $this->_sanitize_options($options);
	}

	public function action()
	{
		return $this->_options['action'];
	}

	public function controller()
	{
		return $this->_options['controller'];
	}

	public function host()
	{
		/* TODO: NEED TO HANDLE GETTING HOST FROM ROUTE */
		return Request::currentURL();
	}

	public function match(Request $request)
	{
		/* absolute root */
		if(!\strlen($this->path()) && !\strlen($request->path()))
			return $this;

		if(!\preg_match($this->_to_regex(), $request->path(), $this->_raw))
			return false;

		return $this;
	}

	public function method()
	{
		return isset($this->_options['method']) ? $this->_options['method'] : 'any';
	}

	public function name()
	{
		return isset($this->_options['name']) ? $this->_options['name'] : null;
	}

	public function options()
	{
		return $this->_options;
	}

	public function options_except($except)
	{
		return \array_diff_key($this->options(), \array_flip($except));
	}

	public function options_filtered($filter)
	{
		return \array_intersect_key($this->options(), \array_flip($filter));
	}

	public function params()
	{
		if(!isset($this->_params))
			foreach($this->segments() as $s)
				if($s->is_param())
					$this->_params[] = $s;

		return $this->_params;
	}

	public function path()
	{
		return $this->_path;
	}

	public function sanitize()
	{
		$ex = \explode(Segment::SEPARATOR, $this->_raw[0]);
		$tx = \explode('/', \str_replace(array('(', ')'), '', $this->path()));

		$j = count($ex);
		for($i = 0; $i < $j; $i++) {
			if(!strlen($tx[$i]) || $tx[$i][0] !== ':')
				continue;

			$this->_options[\substr($tx[$i], 1)] = $ex[$i];
		}

		if(!isset($this->_options['controller']))
			throw new \P3\Routing\Exception\Invalid($this, 'Route matched, but no controller defined');

		if(!isset($this->_options['action']))
			$this->_options['action'] = 'index';
	}

	public function segments()
	{
		if(!isset($this->_segments))
			$this->_segments = Segment::get_from_path($this->path());
		return $this->_segments;
	}

	public function valid($method)
	{
		return ($m = $this->method()) == $method || $m == 'any';
	}

//- Private
	private function _sanitize_options(&$options)
	{
		if(isset($options['to'])) {
			$ex = explode('#', $options['to']);

			$options['controller'] = array_shift($ex);
			$options['action'] = array_shift($ex);
		}

		return $options;
	}

	private function _to_regex()
	{
		if(!isset($this->_regex)) 
			$this->_regex = '!^'.preg_replace('/:[^\(^\^\/)]*/', '[^/^\(]*', \str_replace(')', ')?', $this->path())).'$!';

		return $this->_regex;
	}


//- Magic
	public function __invoke(array $arguments = array())
	{
		if(($a = count($arguments)) !== ($p = count($this->params())))
			throw new \P3\Exception\ArgumentException\Mismatch(
				get_class(), 
				'__invoke', 
				$p,
				$a
			);

		if(!$p)
			return \implode(Segment::SEPARATOR, $this->segments());
		else
			die("WTF NOW, NEED TO PARSE PATH WITH ARGS");
	}
}

?>