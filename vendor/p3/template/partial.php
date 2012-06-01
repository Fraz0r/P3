<?php

namespace P3\Template;

/**
 * Description of partial
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Partial extends Base
{
	protected $_parent;

	public function __construct($path, Base $parent)
	{
		$this->_parent = $parent;

		if($path[0] == '/') {
			parent::__construct($path);
		} else {
			$ex = explode('/', $path);
			$ex[($c = count($ex))-1] = '_'.array_pop($ex).'.tpl';

			$path = (($c == 1 && !is_null($this->_parent)) ? $this->_parent->get_dir() : \P3::config()->action_view->base_path);
			$path .= '/'.implode('/', $ex);

			parent::__construct($path);
		}
	}

	public function init_layout()
	{}

	public function set_layout()
	{}

	public function set_parent($parent)
	{
		$this->_parent = $parent;

		return $this;
	}

	public function render(array $options = [])
	{
		if(!isset($options['locals']))
			$options['locals'] = [];

		$this->_vars = array_merge($this->_vars, $options['locals']);

		if(!isset($options['collection']))
			return parent::render();

		$ret      = '';
		$var_name = substr($this->_basename, 1, (($pos = strpos($this->_basename, '.')) > 0 ? $pos - 1 : null));

		foreach($options['collection'] as $obj) {
			$this->_vars[$var_name] = $obj;
			$ret .= parent::render();
		}

		return $ret;
	}
}

?>