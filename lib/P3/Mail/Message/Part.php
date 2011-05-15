<?php

namespace P3\Mail\Message;

/**
 * A mail part is a Multipart/Alternative message part for a P3\Mail\Message
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Mail\Message
 * @version $Id$
 */
class Part 
{
	/**
	 * MIME Multipart/Alternative boundary to use in rendering
	 * 
	 * @var string
	 * @see render
	 */
	private $_boundary = null;

	/**
	 * Contents of part
	 * 
	 * @var string 
	 */
	private $_contents = null;

	/**
	 * Value for Content-type: header
	 * 
	 * @var string
	 */
	private $_content_type = null;

	/**
	 * Encoding of contents
	 * 
	 * @var string
	 */
	private $_encoding = 'utf-8';

	/**
	 * String to use for end of lines
	 * 
	 * @var string
	 */
	private $_eol      = "\n";


	/**
	 * Options passed to contructor
	 * 
	 * @var array
	 * @see __construct
	 */
	private $_options  = null;

	/**
	 * Transfer encoding to use for message part
	 * 
	 * @var string
	 */
	private $_xfr_enc  = '7bit';

//- Public
	/**
	 * Instantiates new Mail\Message\Part
	 * 
	 * 	Options:
	 * 		encoding:    	Encoding of message (utf-8 by default)
	 * 		xfr_encoding:	Transfer Encoding for message part
	 * 		boundary:    	MIME boundary to use for rendering, also setable with ->boundary after instantiating
	 * 		content_type:	Content type of message part ("text/plain" by default)
	 * 
	 * @param string $contents contents of message part
	 * @param array $options options
	 */
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

	/**
	 * Retreives header (without EOL)
	 * 
	 * @param string $type which header to retreive
	 * @return string header 
	 */
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

	/**
	 * Renders message part for Mail\Message
	 * 
	 * @param boolean $include_headers Whether or not to render headers
	 * @return string rendered message part
	 * @see P3\Mail\Message::_parseParts()
	 */
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

	/**
	 * Sets MIME Multipart/Mixed boundary to use in rendering
	 * 
	 * @param string $boundary boundary to set
	 * @see renderContents
	 */
	public function setBoundary($boundary)
	{
		$this->_boundary = $boundary;
	}
}

?>