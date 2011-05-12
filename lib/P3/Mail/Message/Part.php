<?php

namespace P3\Mail\Message;

/**
 * Description of Part
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Part 
{
	private $_contents = null;
	private $_options  = null;

	public function __construct($contents, array $options = array())
	{
		$this->_contents = $contents;
		$this->_options  = $options;
	}
}

?>