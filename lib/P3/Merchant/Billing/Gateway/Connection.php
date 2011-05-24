<?php

namespace P3\Merchant\Billing\Gateway;
use       P3\Net\Exception;
use       P3\Net\HTTP;

/**
 * This class wraps Net\HTTP to add capability of retries
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Merchant\Billing\Gateway
 * @version $Id$
 */
class Connection 
{
	/**
	 * Maximum amount of times to try exceptions marked as Retriable
	 */
	const MAX_RETRIES  = 3;

	/**
	 * Timeout for opening socket
	 */
	const OPEN_TIMEOUT = 60;

	/**
	 * Timeout for reading from socket
	 */
	const READ_TIMEOUT = 60;

	/**
	 * Not yet used
	 */
	const VERIFY_PEER  = true;

	/**
	 * Try retries on exceptions not marked as Retriable
	 */
	const RETRY_SAFE   = false;

	/**
	 * Socket endpoint
	 * 
	 * @var string
	 */
	public $endpoint     = null;

	/**
	 * Open timeout
	 * 
	 * @var int
	 */
	public $open_timeout = null;

	/**
	 * Read timeout
	 * 
	 * @var int
	 */
	public $read_timeout = null;

	/**
	 * Retry safe setting, see above
	 * 
	 * @var boolean 
	 */
	public $retry_safe   = null;

	/**
	 * Not yet used
	 * 
	 * @var boolean
	 */
	public $verify_peer  = null;

	/**
	 * HTTP Socket
	 * 
	 * @var P3\Net\HTTP
	 */
	private $_http = null;

	/**
	 * Current number of retries made
	 * 
	 * @var int
	 */
	private $_retries = null;

	/**
	 * Headers for POST request
	 * 
	 * @var array
	 */
	public static $POST_HEADERS = array('Content-Type' => 'application/x-www-form-urlencoded');

//- Public
	/**
	 * Instatiate Gateway Connection (does not connect untill a request is made)
	 * 
	 * @param string $endpoint enpoint for HTTP socket
	 */
	public function __construct($endpoint)
	{
		$this->endpoint     = is_a($endpoint, 'P3\Net\HTTP\Request') ? $endpoint : new HTTP\Request($endpoint);
		$this->open_timeout = self::OPEN_TIMEOUT;
		$this->read_timeout = self::READ_TIMEOUT;
		$this->retry_safe   = self::RETRY_SAFE;
		$this->verify_peer  = self::VERIFY_PEER;
	}

	/**
	 * Handle GET/POST requests
	 * 
	 * @param string $method method, GET or POST
	 * @param string $body body, only used in POST
	 * @param array $headers array of headers to send with request
	 * 
	 * @return Net\HTTP\Response returned response 
	 */
	public function request($method, $body = null, array $headers = array())
	{
		if(is_null($this->_retries))
			$this->_retries = self::MAX_RETRIES;

		try {
			$result = null;

			switch($method){
				case 'get':
					if(!is_null($body) || !empty($body))
						throw new \P3\Exception\ArgumentError("GET requests do not support a request body");

					return $this->http->get($this->enpoint, array(), $headers);
					break;
				case 'post':
					return $this->http->post($this->endpoint, $body, $headers);
					break;
			}
		} catch(Exception\RetriableConnectionError $e) {
			if(--$this->_retries)
				$this->request($method, $body, $headers);

			throw new Exception\ConnectionError($e->getMessage());
		} catch(Exception\ConnectionError $e) {
			if($this->retry_safe && --$this->_retries)
				$this->request ($method, $body, $headers);

			throw new Exception\Unknown($e->getMessage());
		}
	}

	/**
	 * Incoming..
	 */
	private function _configure_ssl(&$http)
	{
	}

	/**
	 * HTTP connection singleton
	 * 
	 * @return P3\Net\HTTP http connection
	 */
	private function _http()
	{
		$http = new HTTP($this->endpoint->host(), $this->endpoint->port());

		$this->_configure_ssl($http);

		return $http;
	}

	/**
	 * Current only used to return http connection
	 * 
	 * @param type $var
	 * @return mixed 
	 * @magic
	 */
	public function __get($var)
	{
		switch($var) {
			case 'http':
				if(is_null($this->_http))
					$this->_http = $this->_http();

				return $this->_http;
				break;
		}

		return null;
	}
}

?>