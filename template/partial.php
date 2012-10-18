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

	/**
	 * Path to partial template.  Based on the passed $path, the $path will be modified in one of three ways
	 * 
	 * Examples:
	 * 	/home/tim/test.tpl => /home/test                             [Root] (Starts with '/')
	 * 	shared/partial => [action_view_base]/shared/_partial.tpl     [absolute] (Contains a '/')
	 * 	partial => [parent_base|action_view_base]/_partial.tpl       [relative] ('Doesnt have a '/')
	 * 
	 * @param string $path
	 * @param \P3\Template\Base $parent
	 */
	public function __construct($path, $parent = null)
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

//- Public
	/**
	 * Partials do not have layouts
	 */
	public function init_layout()
	{}

	/**
	 * Partials do not have layouts
	 */
	public function set_layout()
	{}

	/**
	 * Assigns a parent to the partial.  This can be anything that extends this class
	 * 	including: action_view, layout, and other partials
	 * 	
	 * @param type $parent
	 * @return \P3\Template\Partial
	 */
	public function set_parent($parent)
	{
		$this->_parent = $parent;

		return $this;
	}

	/**
	 * Renders partial, using $options
	 * 
	 * Options:
	 * 	locals     => An array of "variables" to merge with already assigned vars ($this->_vars)
	 * 	collection => A collection of objects to loop through, and render this partial once per iteration
	 * 
	 * @param array $options
	 * @return string
	 */
	public function render(array $options = [])
	{
		if(isset($options['partial']))
			return parent::render($options);

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