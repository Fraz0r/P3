<?php

namespace P3\Mail;

/**
 * Description of Message
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Message 
{
	private $_body    = '';
	private $_from    = null;
	private $_headers = array();
	private $_subject = null;
	private $_to      = null;
	private $_options = null;

	public function __construct($to, $subject, $contents, array $options = array())
	{
		$this->_to      = $to;
		$this->_subject = $subject;
		$this->_body    = is_string($contents) ? $contents : $this->_parseParts($contents);
		$this->_options = $options;
	}

	public function addHeader($header)
	{
		$this->_headers[] = $header;
	}

	public function deliver()
	{
		mail($this->_to, $this->_subject, $this->_body, $this->_headers());
	}

//- Private
	private function _headers()
	{
		if(!is_null($this->_from))
			$this->addHeader('From: '.$this->_from);

		return implode("\r\n", $this->_headers);
	}

	private function _parseParts($contents)
	{
		if(is_array($contents)) {
		} elseif(is_subclass_of($contents, 'P3\Mail\Message\Part')) {
			echo 'hit';
		} else {
			/* Need Exception */
		}
	}

}

?>