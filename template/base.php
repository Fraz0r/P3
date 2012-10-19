<?php

namespace P3\Template;

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base
{
	/**
	 * Name of buffer currently being written to
	 * 
	 * @var string
	 */
	protected $_active_buffer;

	/**
	 * Basename of template file
	 * 
	 * @var string
	 */
	protected $_basename;

	/**
	 * Full directory path to template file
	 * 
	 * @var string
	 */
	protected $_dir;

	/**
	 * Layout for template (if any)
	 * 
	 * @var \P3\Template\Layout
	 */
	protected $_layout;

	/**
	 * Full path to template file (including basename)
	 * 
	 * @var string
	 */
	protected $_path;

	/**
	 * Variables assigned to template.  These will be extracted uppon render
	 * 
	 * @see extract()
	 * @var type 
	 */
	protected $_vars = [];

	/**
	 * Collection of buffers.  These can be written/read from any template file
	 * 
	 * @var array
	 */
	private static $_buffers = [];

	/**
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->_basename = basename($path);
		$this->_dir  = dirname($path);
		$this->_path = $path;
	}

//- Public
	/**
	 * Assigns variabls to template, using keys as var names
	 * 
	 * @param array $vars
	 * @return \P3\Template\Base
	 */
	public function assign(array $vars)
	{
		foreach($vars as $k => $v)
			$this->__set($k, $v);

		return $this;
	}

	/**
	 * Add $contents to the given buffer
	 * 
	 * @param string $buffer name of buffer
	 * @param string $contents contents for buffer
	 * @return void
	 */
	public function append_to($buffer, $contents)
	{
		if(!isset(self::$_buffers[$buffer]))
			self::$_buffers[$buffer] = '';

		self::$_buffers[$buffer] .= $contents;
	}

	public function buffer_exists($buffer_name)
	{
		return isset(self::$_buffers[$buffer_name]);
	}

	/**
	 * Add $contents to the given buffer, using the return from passed $closure
	 * 
	 * @param string $buffer name of buffer
	 * @param \P3\Template\callable $closure
	 * @return void
	 */
	public function content_for($buffer, callable $closure)
	{
		ob_start();
		$closure();
		$this->append_to($buffer, ob_get_clean());
	}

	/**
	 * End an already started buffer
	 * 
	 * @see start_content_for()
	 * 
	 * @param string $buffer
	 * @return boolean
	 */
	public function end_content_for($buffer)
	{
		// TODO: Need exception here if another buffer is not the active one
		$this->append_to($buffer, ob_get_contents());
		return ob_end_clean();
	}

	/**
	 * Checks to see if template is readable, returns boolean
	 * 
	 * @return boolean
	 */
	public function exists()
	{
		return is_readable($this->_path);
	}

	/**
	 * Returns the basename of the template file
	 * 
	 * @return string
	 */
	public function get_basename()
	{
		return $this->_basename;
	}

	/**
	 * Retrieves buffer for rendering
	 * 
	 * @param string $buffer name of buffer to get
	 * @return string
	 * @throws Exception\UnknownBuffer
	 */
	public function get_buffer($buffer)
	{
		if(!isset(self::$_buffers[$buffer]))
			throw new Exception\UnknownBuffer($this->_basename, $buffer);

		return self::$_buffers[$buffer];
	}

	/**
	 * Get full directory path to template file
	 * 
	 * @return string
	 */
	public function get_dir()
	{
		return $this->_dir;
	}

	/**
	 * Returns layout assigned to template
	 * 
	 * @return Layout
	 */
	public function get_layout()
	{
		return $this->_layout;
	}

	/**
	 * Get full directory path to template file, including basename
	 * 
	 * @return string
	 */
	public function get_path()
	{
		return $this->_path;
	}

	/**
	 * Retreives variables assigned to template
	 * 
	 * @return array
	 */
	public function get_vars()
	{
		return $this->_vars;
	}

	/**
	 * Creates and assigns a Layout, using $path
	 * 
	 * @param string $path Path to layout (relative to ./app/views/layouts)
	 */
	public function init_layout($path)
	{
		$this->set_layout(new Layout($path));

		return $this;
	}

	/**
	 * Sets contents of $buffer to $contents
	 * 
	 * 	Note:  This will override any previously assigned content from the buffer
	 * 
	 * @param string $buffer
	 * @param string $contents
	 */
	public function set_buffer($buffer, $contents)
	{
		self::$_buffers[$buffer] = $contents;
	}

	/**
	 * Assignes $layout to template
	 * 
	 * @param \P3\Template\Layout $layout
	 * @return \P3\Template\Base
	 */
	public function set_layout(Layout $layout)
	{
		$this->_layout = $layout;

		return $this;
	}

	/**
	 * Starts a buffer under the name $buffer
	 * 
	 * @param string $buffer
	 */
	public function start_content_for($buffer)
	{
		// TODO: Need exception here if another buffer is already started

		$this->_active_buffer = $buffer;
		ob_start();
	}

	/**
	 * If $what is null, renders template and returns contents
	 * if $what is an array, attempts to forward render to appropriate area (currently only partials)
	 * 
	 * @param null|array $what
	 * @return string
	 * @throws Exception\UnknownRender
	 * @throws \P3\System\Exception\FileNotFound
	 */
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

	/**
	 * Prints buffer assigned to $buffer.  Defaults to action view.
	 * 
	 * @param type $buffer
	 */
	public function yield($buffer = 'p3_view')
	{
		return $this->get_buffer($buffer);
	}

//- Static
	/**
	 * Renders partial using $path.  If a $templatable parent is passed, vars are
	 * 	transfered from the parent to the partial uppon render
	 * 
	 * @param string $path
	 * @param \P3\Template\Base $templatable Parent (if any)
	 * @param array $options options for render (if any)
	 * @return string contents of partial render
	 * @throws Exception\UnknownRender
	 */
	public static function render_partial($path, $templatable = null, array $options = [])
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
	/**
	 * This method is used as a convinience access for $this->_vars
	 * 	Throws exception if var doesn't exist
	 * 
	 * @param string $var
	 * @return mixed
	 * @throws Exception\VarNoExist
	 */
	public function __get($var)
	{
		if(!isset($this->_vars[$var]))
			throw new Exception\VarNoExist('%s was never set, but was attempted access', array($var));

		return $this->_vars[$var];
	}

	/**
	 * This method is used as a convinience method for setting $this->_vars
	 * 
	 * @param string $var
	 * @param mixed $val
	 */
	public function __set($var, $val)
	{
		$this->_vars[$var] = $val;
	}
}

?>