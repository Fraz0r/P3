<?php

namespace P3\XML;

/**
 * Description of Builder
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Builder 
{
	protected $_contents = '';

	private $_indent = 0;

	public function __construct(array $options = array()) 
	{
		if(isset($options['indent']))
			$this->_indent = $options['indent'];
	}

	public function contents()
	{
		return $this->_contents;
	}

	public function cdata($data)
	{
		$this->_indent();
		$this->_startTag('![CDATA[', null, false);
		$this->text($data);
		$this->_contents .= ']]>';
		$this->_newLine();

	}

	public function comment($comment)
	{
		$this->_indent();
		$this->_startTag('!--', null, false);
		$this->_contents .= ' '.$comment.' -->';
		$this->_newLine();
	}

	public function instruct()
	{
		$this->_contents = '<?xml version="1.0" encoding="UTF-8"?>';
		$this->_newLine();
	}

	public function tag($name, $arguments = null, $closure = null)
	{
		$arguments = !is_null($arguments) ? array($arguments) : array();

		if(!is_null($closure))
			$arguments[] = $closure;

		$this->__call($name, $arguments);
	}

	public function text($text)
	{
		$this->_contents .= $text;
	}

	public function __call($name, array $arguments = array())
	{
		$text = null;
		$attrs = null;
		$closure = null;


		if(0 < ($c = count($arguments))) {
			if(is_callable($arguments[--$c]) && is_object($arguments[$c]))
				$closure = array_pop($arguments);

			foreach($arguments as $arg) {
				if(is_array($arg)) {
					if(is_null($attrs))
						$attrs = array();

					$attrs = array_merge($attrs, $arg);
				} elseif(is_string($arg)) {
					if(is_null($text))
						$text = '';

					$text .= $arg;
				} else {
					var_dump("TODO: NEED EXCEPTION (unkown argument)");
					die;
				}
			}

			if(!is_null($closure)) {
				if(!is_null($text)) {
					var_dump("TODO: NEED EXCEPTION (cant mix text and closures)");
					die;
				}
				
				$this->_indent();
				$this->_startTag($name, $attrs);
				$this->_newLine();

				try {
					$this->_nestedStructures($closure);
					$this->_indent();
					$this->_endTag($name);
					$this->_newLine();
				} catch(Exception $e) {
					$this->_indent();
					$this->_endTag($name);
					$this->_newLine();
				}
			} elseif(is_null($text)) {
			} else {
				$this->_indent();
				$this->_startTag($name, $attrs);
				$this->text($text);
				$this->_endTag($name);
				$this->_newLine();
			} 
		}
	}

//- Private
	private function _endTag($name)
	{
		$this->_contents .= '</'.$name.'>';
	}

	private function _indent($level = null)
	{
		$level = is_null($level) ? $this->_indent : $level;

		for($i = 0; $i < $level; $i++)
			$this->_contents .= "\t";
	}

	private function _nestedStructures($closure)
	{
		$builder = new self(array('indent' => $this->_indent + 1));
		$closure($builder);
		$this->_contents .= $builder->contents();
	}

	private function _newLine()
	{
		$this->_contents .= "\n";
	}

	private function _startTag($name, $attrs = null, $close = true)
	{
		$this->_contents .= '<'.$name;

		if(is_array($attrs) && count($attrs)) {
			$this->_contents .= ' ';

			foreach($attrs as $k => &$v)
				$v = $k.'="'.$v.'"';

			$this->_contents .= implode(' ', $attrs);
		}

		if($close)
			$this->_contents .= '>';
	}
}

?>