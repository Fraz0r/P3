<?php 

namespace P3\Net;
use P3\Net\Header\Collection as HeaderList;

class HTTP {
	private $host;
	private $port;
	private $headers;

	public function __construct($host, $port = null) 
	{
		$this->host    = $host;
		$this->port    = is_null($port) ? 80 : $port;
		$this->headers = new HeaderList(array('Host' => $host), "\r\n");
	}

	public function get($path, $params = array(), $headers = array()) 
	{
		return $this->send($path, 'get', $params, null, $headers);
	}

	public function post($path, $body, $headers = array()) 
	{
		return $this->send($path, 'post', array(), $body, $headers);
	}

	public static function serialize_auth($user, $pass) 
	{
		return base64_encode("$user:$pass");
	}

	public static function serialize_params($params)
	{
		$query_string = array();
		foreach ($params as $key => $value) {
			$query_string[] = urlencode($key) . '=' . urlencode($value);
		}
		return implode('&', $query_string);
	}

	private function send($endpoint, $method, $params = array(), $body = null, $headers = array())
	{  
		$enpoint = is_a($endpoint, 'P3\Net\HTTP\Request') ? $endpoint : new HTTP\Request($endpoint);

		$params = self::serialize_params($params);
		$this->headers->add($headers);  
		
		if($method == 'post') {
			$this->headers->add(array('Content-type' => 'application/x-www-form-urlencoded'));
			$this->headers->add(array('Content-length' => strlen($body)));
		}

		if($method == 'get')
			$params = '?'.$params;

		$this->headers->add(array('Connection' => 'Close'));

		$this->request = strtoupper($method) . " {$endpoint->path()}".($method == 'get' ? $params : '')." HTTP/1.1\r\n";
		$this->request .= $this->headers->to_s() . "\r\n\r\n";    

		if($method == 'post')
			$this->request .= $body."\r\n\r\n";

		if (FALSE !== ($fp = @fsockopen('ssl://'.$this->host, $this->port, $errno, $errstr, 15))) {
			if (fwrite($fp, $this->request)) {
				while (!feof($fp)) {
					$this->response .= fread($fp, 4096);
				}
			}
			fclose($fp);
		} else {
			throw new Exception\RetriableConnectionError("[HTTP %s] could not establish connection with %s (%s)", array($errno, $this->host, $errstr));
		}

		return new HTTP\Response($this->response);
	}
}

?>