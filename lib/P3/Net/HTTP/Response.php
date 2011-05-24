<?php

namespace P3\Net\HTTP;
use       P3\Net\Header\Collection as HeaderList;

/**
 * This class is wraps an HTTP response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Response 
{
	/**
	 * HTTP Response Code
	 * 
	 * @var int
	 */
	public $code    = null;

	/**
	 * Collection of headers from response
	 * 
	 * @var array
	 */
	public $headers = array();

	/**
	 * Body of HTTP Response
	 * 
	 * @var string
	 */
	public $body    = null;

	/**
	 * Full HTTP Response (including headers)
	 * 
	 * @var string
	 */
	public $full     = null;

//- Public
	/**
	 * Instantiate new HTTP Reponse
	 * 
	 * @param string $response_text HTTP response
	 */
	public function __construct($response_text)
	{
		$this->full = $response_text;
		$this->_parse($response_text);
	}

//- Private
	/**
	 * Parse response text into self
	 * 
	 * @param string $response HTTP response
	 */
	private function _parse($response) 
	{
		$parsed = str_replace("\r\n", "\n", $response);

		list($headers, $body) = explode("\n\n", $parsed, 2);

		$headers = new HeaderList($headers);

		$this->code    = $headers->getResponseCode();
		$this->headers = $headers->to_a();
		$this->body    = $body;
	}
}

?>