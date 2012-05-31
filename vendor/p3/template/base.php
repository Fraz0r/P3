<?php

namespace P3\Template;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base
{
	protected $_active_buffer;
	protected $_base;
	protected $_dir;
	protected $_layout;
	protected $_path;
	protected $_vars = [];

	private static $_buffers = [];

	public function __construct($path)
	{
		$this->_base = basename($path);
		$this->_dir  = dirname($path);
		$this->_path = $path;
	}

//- Public
	public function assign(array $vars)
	{
		foreach($vars as $k => $v)
			$this->__set($k, $v);
	}

	public function append_to($buffer, $contents)
	{
		if(!isset(self::$_buffers[$buffer]))
			self::$_buffers[$buffer] = '';

		self::$_buffers[$buffer] .= $contents;
	}

	public function content_for($buffer, callable $closure)
	{
		$this->append_to($buffer, $closure());
	}

	public function end_content_for($buffer)
	{
		// TODO: Need exception here if another buffer is not the active one
		$this->append_to($buffer, ob_get_contents());
		return ob_end_clean();
	}

	public function exists()
	{
		return is_readable($this->_path);
	}

	public function get_buffer($buffer)
	{
		if(!isset(self::$_buffers[$buffer]))
			throw new Exception\UnknownBuffer($this->_base, $buffer);

		return self::$_buffers[$buffer];
	}

	public function init_layout($path)
	{
		$this->set_layout(new Layout(\P3\ROOT.'/app/views/layouts/'.$path));
	}

	public function set_buffer($buffer, $contents)
	{
		self::$_buffers[$buffer] = $contents;
	}

	public function set_layout(Layout $layout)
	{
		$this->_layout = $layout;
	}

	public function start_content_for($buffer)
	{
		// TODO: Need exception here if another buffer is already started

		$this->_active_buffer = $buffer;
		ob_start();
	}

	public function render()
	{
		if(!$this->exists())
			throw new \P3\System\Exception\FileNotFound($this->_path);

		ob_start();
			extract($this->_vars);
			include($this->_path);
			$contents = ob_get_contents();
		ob_end_clean();

			if($this->_layout) {
				ob_start();
					$this->_layout->assign($this->_vars);
					$this->_layout->set_buffer('p3_view', $contents);
					$contents = $this->_layout->render();
				ob_end_clean();
			}

		return $contents;
	}

	public function yield($buffer = 'p3_view')
	{
		echo $this->get_buffer($buffer);
	}

//- Magic
	public function __get($var)
	{
		if(!isset($this->_vars[$var]))
			throw new Exception\VarNoExist('%s was never set, but was attempted access', array($var));

		return $this->_vars[$var];
	}

	public function __set($var, $val)
	{
		$this->_vars[$var] = $val;
	}
}

?>