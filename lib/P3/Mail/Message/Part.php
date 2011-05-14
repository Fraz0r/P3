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

	public function header($type)
	{
		switch($type) {
			case 'content':
				return 'Content-Type: '.$this->_content_type.'; charset="'.$this->_encoding.'"';
			case 'xfr':
			case 'transfer':
				return 'Transfer-Encoding: '.$this->_xfr_enc;
			default:
				return null;
		}
	}

	public function renderContents($include_headers = true)
	{
		$eol = $this->_eol;

		$ret = '';
		
		if($include_headers) {
			if(!is_null($this->_boundary))
				$ret .= '--'.$this->_boundary.$eol;

			$ret .= $this->header('content').$eol;
			$ret .= $this->header('transfer').$eol.$eol;
		}

		$ret .= $this->_contents;

		if($include_headers && !is_null($this->_boundary))
			$ret .= $eol.$eol.'--'.$this->_boundary.'--'.$eol.$eol;

		return $ret;
	}

	public function setBoundary($boundary)
	{
		$this->_boundary = $boundary;
	}
}

?>