<?php

namespace P3\Net\HTTP;
use       P3\Net\Header\Collection as HeaderList;

/**
 * Description of Response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Response 
{
	public $code    = null;
	public $headers = array();
	public $body    = null;
	public $full     = null;

	public function __construct($response_text)
	{
		$this->full = $response_text;
		$this->_parse($response_text);
	}

	private function _parse($response) 
	{
		$parsed = str_replace("\r\n", "\n", $response);

		list($headers, $body) = explode("\n\n", $parsed, 2);

		$headers = new HeaderList($headers);

		$this->code    = $headers->get_response_code();
		$this->headers = $headers->to_a();
		$this->body    = $body;
	}
}

?>