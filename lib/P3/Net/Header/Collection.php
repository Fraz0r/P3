<?php

namespace P3\Net\Header;

/**
 * This class is used to interact with a collection of headers
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Net\Header
 * @version $Id$
 */
class Collection {
	/**
	 * Header collection
	 * 
	 * @var type 
	 */
	private $_headers = array();


	/**
	 * HTTP Response Code
	 * 
	 * @var int 
	 */
	private $_responseCode = null;

	/**
	 * Line ending to use
	 * 
	 * @var type 
	 */
	private $_linebreak = null;

//- Public
	/**
	 * Instantiate new header collection
	 * 
	 * @param array,string $headers array of headers, or string from HTTP request/response
	 * @param type $linebreak linebreak to use as glue for headers
	 */
	public function __construct($headers = array(), $linebreak = "\n") 
	{
		$this->_linebreak = $linebreak;
		$this->_headers   = $headers;

		if (is_string($this->_headers))
			$this->_parseHeaderString();
	}

	/**
	 * Renders headers into string
	 * 
	 * @return string
	 */
	public function to_s() 
	{
		$headers = array();
		foreach ($this->_headers as $header => $value) {
			$headers[] = "$header: $value";
		}
		return implode("\r\n", $headers);
	}

	/**
	 * Returns headers as array
	 * 
	 * @return array 
	 */
	public function to_a()
	{
		return $this->_headers;
	}

	/**
	 * Renders headers into string
	 * 
	 * @return string 
	 */
	public function __toString()
	{
		return $this->to_s();
	}

	/**
	 * Add headeras
	 * 
	 * @param array $headers 
	 */
	public function add(array $headers)
	{
		$this->_headers = array_merge($this->_headers, $headers);
	}

	/**
	 * Get header
	 * 
	 * @param string $header header to grab
	 * @return string contents of header field 
	 */
	public function get($header)
	{
		return isset($this->_headers[$header]) ? $this->_headers[$header] : null;
	}

	/**
	 * Returns response code from headers
	 * 
	 * @return int 
	 */
	public function getResponseCode()
	{
		return $this->_responseCode;
	}

//- Private
	/**
	 * Parses stringed headers into array, also determining response code
	 */
	private function _parseHeaderString()
	{
		$replace = ($this->_linebreak == "\n" ? "\r\n" : "\n");
		$headers = str_replace($replace, $this->_linebreak, trim($this->_headers));
		$headers = explode($this->_linebreak, $headers);
		$this->_headers = array();
		if (preg_match('/^HTTP\/\d\.\d (\d{3})/', $headers[0], $matches)) {
			$this->_responseCode = $matches[1];
			array_shift($headers);
		}
		foreach ($headers as $string) {
			list($header, $value) = explode(': ', $string, 2);
			$this->_headers[$header] = $value;
		}
	}
}

?>