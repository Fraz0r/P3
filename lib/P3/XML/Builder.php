<?php

namespace P3\XML;

/**
 * Netsable OOP XML builder
 * 
 * 	Example:
 * 		$xml = new P3\XML\Builder;
 * 		$xml->instruct();
 * 		$xml->people(function(&$xml){
 * 			$xml->person(&$xml){
 * 				$xml->name('John Doe');
 * 			});
 * 		});
 * 		$xml->contents();
 * 
 * 	Returns:
		<?xml version="1.0" encoding="UTF-8"?>';
 * 		<people>
 * 			<person>
 * 				<name>John Doe</name>
 * 			</person>
 *		</people>
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\XML
 * @version $Id$
 */
class Builder 
{
	/**
	 * Container for string parser
	 * 
	 * @var string
	 */
	protected $_contents = '';

	/**
	 * How many levels this builder is nested
	 * 
	 * @var int
	 */
	private $_indent = 0;

//- Public
	/**
	 * Instantiate new XML Builder
	 * 
	 * 	Options:
	 * 		indent: Levels this builder is indented
	 * 
	 * @param array $options 
	 */
	public function __construct(array $options = array()) 
	{
		if(isset($options['indent']))
			$this->_indent = $options['indent'];
	}

	/**
	 * Returns parsed contents
	 * 
	 * @return string 
	 */
	public function contents()
	{
		return $this->_contents;
	}

	/**
	 * Creates <![CDATA[]]> block, containg $data
	 * 
	 * @param string $data  data to insert into newly created CDATA 
	 */
	public function cdata($data)
	{
		$this->_indent();
		$this->_startTag('![CDATA[', null, false);
		$this->text($data);
		$this->_contents .= ']]>';
		$this->_newLine();

	}

	/**
	 * Creates comment
	 * 
	 * @param string $comment comment
	 */
	public function comment($comment)
	{
		$this->_indent();
		$this->_startTag('!--', null, false);
		$this->_contents .= ' '.$comment.' -->';
		$this->_newLine();
	}

	/**
	 * Add document instructions
	 * 
	 * TODO: Add options to instruct() in XML Builder
	 */
	public function instruct()
	{
		$this->_contents = '<?xml version="1.0" encoding="UTF-8"?>';
		$this->_newLine();
	}

	/**
	 * Create and append tag
	 * 
	 * @param string $name tag name
	 * @param array $arguments arguments for tag 
	 * @param type $closure  closure, only if nesting
	 * 
	 * @return void
	 */
	public function tag($name, $arguments = null, $closure = null)
	{
		$arguments = !is_null($arguments) ? array($arguments) : array();

		if(!is_null($closure))
			$arguments[] = $closure;

		$this->__call($name, $arguments);
	}

	/**
	 * Appends text to contents
	 * 
	 * @param type $text 
	 * @return void
	 */
	public function text($text)
	{
		$this->_contents .= $text;
	}

//- Magic
	/**
	 * This is where the magic happens...
	 * 
	 * @param type $name
	 * @param array $arguments 
	 * @magic
	 */
	public function __call($name, array $arguments = array())
	{
		$text    = null;
		$attrs   = null;
		$closure = null;


		if(0 < ($c = count($arguments))) {
			if(is_callable($arguments[--$c]) && is_object($arguments[$c]))
				$closure = array_pop($arguments);

			foreach($arguments as $arg) {
				if(is_numeric($arg))
					$arg = (string)$arg;

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