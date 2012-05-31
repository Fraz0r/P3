<?php

namespace P3\Net\Http;

/**
 * Description of response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Response 
{
	const PROTOCOL_VERSION = 1.1;

	const STATUS_OK          = 200;
	const STATUS_CREATED     = 201;
	const STATUS_ACCEPTED    = 202;
	const STATUS_PARTIAL     = 203;
	const STATUS_NO_RESPONSE = 204;

	const STATUS_MOVED        = 301;
	const STATUS_FOUND        = 302;
	const STATUS_NOT_MODIFIED = 304;

	const STATUS_BAD_REQUEST      = 400;
	const STATUS_UNAUTHORIZED     = 401;
	const STATUS_PAYMENT_REQUIRED = 402;
	const STATUS_FORBIDDEN        = 403;
	const STATUS_NOT_FOUND        = 404;

	const STATUS_INTERNAL_ERROR  = 500;
	const STATUS_NOT_IMPLEMENTED = 501;

	private static $_STATUS_LABELS = array(
		self::STATUS_OK          => 'OK',
		self::STATUS_CREATED     => 'Created',
		self::STATUS_ACCEPTED    => 'Accepted',
		self::STATUS_PARTIAL     => 'Partial Information',
		self::STATUS_NO_RESPONSE => 'No Response',

		self::STATUS_MOVED        => 'Moved Permanently',
		self::STATUS_FOUND        => 'Found',
		self::STATUS_NOT_MODIFIED => 'Not Modified',

		self::STATUS_BAD_REQUEST      => 'Bad Request',
		self::STATUS_UNAUTHORIZED     => 'Unauthorized',
		self::STATUS_PAYMENT_REQUIRED => 'Payment Required',
		self::STATUS_FORBIDDEN        => 'Forbidden',
		self::STATUS_NOT_FOUND        => 'Not Found',

		self::STATUS_INTERNAL_ERROR  => 'Internal Server Error',
		self::STATUS_NOT_IMPLEMENTED => 'Not Implemented'
	);

	private $_body;
	private $_headers;
	private $_code;

	public function __construct($body, array $headers = array(), $code = self::STATUS_OK)
	{
		$this->_body    = $body;
		$this->_code    = $code;
		$this->_headers = $headers;

		if($code !== self::STATUS_OK)
			array_unshift($this->_headers, self::get_http_header($this));
	}

	public function body($body = null)
	{
		if(is_null($body))
			return $this->_body;
		else
			$this->_body = $body;
	}

	public function code($code = null)
	{
		if(is_null($code))
			return $this->_code;
		else
			$this->_code = $code;
	}

	public function headers($headers = null)
	{
		if(is_null($headers))
			return $this->_headers;
		else
			$this->_headers = $headers;
	}

	public function send()
	{
		if(\P3::config()->trap_extraneous_output)
			ob_end_clean();

		self::process($this);
	}

//- Public Static
	public static function from_array(array $response = [])
	{
		//TODO: Refactor with action controllers implementation
		return new self($response[2], $response[1], $response[0]);
	}

	public static function get_http_header($response)
	{
		$code = $response->code();

		return 'HTTP/'.self::PROTOCOL_VERSION.' '.$code.' '.self::$_STATUS_LABELS[$code];
	}

	public static function process(\P3\Net\Http\Response $response)
	{
		$code = (string)$response->code();

		if($code[0] == 3)
			return self::_redirect($response);

		self::_render($response);
	}

//- Private Static
	private static function _redirect($response)
	{
		foreach($response->headers() as $header)
			header($header);
	}

	private static function _render($response)
	{
		foreach($response->headers() as $header)
			header($header);

		echo $response->body();
	}
}

?>