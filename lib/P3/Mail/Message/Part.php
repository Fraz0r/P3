<?php

namespace P3\Mail\Message;

/**
 * Description of Part
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Part 
{
	private $_boundary = null;
	private $_contents = null;
	private $_content_type = null;
	private $_encoding = 'utf-8';
	private $_eol      = "\n";
	private $_options  = null;
	private $_xfr_enc  = '7bit';

	public function __construct($contents, array $options = array())
	{
		$this->_contents = $contents;
		$this->_options  = $options;

		if(isset($options['encoding']))
			$this->_encoding = $options['encoding'];

		if(isset($options['xfr_encoding']))
			$this->_xfr_enc = $options['xfr_encoding'];

		if(isset($options['boundary']))
			$this->_boundary = $options['boundary'];

		$this->_content_type = isset($options['content_type']) ? $options['content_type'] : 'text/plain';
	}

	public function renderContents()
	{
		$eol = $this->_eol;

		$ret = '';
		
		if(!is_null($this->_boundary))
			$ret .= '--'.$this->_boundary.$eol;

		$ret .= 'Content-Type: '.$this->_content_type.'; charset="'.$this->_encoding.'"'.$eol;
		$ret .= 'Transfer-Encoding: '.$this->_xfr_enc.$eol.$eol;
		$ret .= $this->_contents.$eol.$eol;

		if(!is_null($this->_boundary))
			$ret .= '--'.$this->_boundary.'--'.$eol.$eol;

		return $ret;
	}

	public function setBoundary($boundary)
	{
		$this->_boundary = $boundary;
	}
}

?>