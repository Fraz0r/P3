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

	public function __construct()
	{
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

}

?>