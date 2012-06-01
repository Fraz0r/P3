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
	protected $_basename;
	protected $_dir;
	protected $_layout;
	protected $_path;
	protected $_vars = [];

	protected static $_BASE_VIEW_PATH;

	private static $_buffers = [];

	public function __construct($path)
	{
		$this->_basename = basename($path);
		$this->_dir  = dirname($path);
		$this->_path = $path;
	}

//- Public
	public function assign(array $vars)
	{
		foreach($vars as $k => $v)
			$this->__set($k, $v);

		return $this;
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

	public function get_basename()
	{
		return $this->_basename;
	}

	public function get_dir()
	{
		return $this->_dir;
	}

	public function get_buffer($buffer)
	{
		if(!isset(self::$_buffers[$buffer]))
			throw new Exception\UnknownBuffer($this->_basename, $buffer);

		return self::$_buffers[$buffer];
	}

	public function get_vars()
	{
		return $this->_vars;
	}

	public function init_layout($path)
	{
		$this->set_layout(new Layout($path));
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

	public function render($what = null)
	{
		if(!is_null($what)) {
			if(!is_array($what) || !isset($what['partial']))
				throw new Exception\UnknownRender;

			return self::render_partial($what['partial'], $this, (count($what) > 1 ? $what[0] : []));
		}

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

//- Static
	public static function render_partial($path, Base $templatable, array $options = [])
	{
		$partial = new Partial($path, $templatable);

		if(!is_null($templatable)) {
			if(!is_a($templatable, 'P3\Template\Base'))
				throw new Exception\UnknownRender;

			$partial->assign($templatable->get_vars());
		}

		return $partial->render($options);
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