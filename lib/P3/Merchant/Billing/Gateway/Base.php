<?php

namespace P3\Merchant\Billing\Gateway;

/**
 * Base class for gateway interfaces
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Merchant\Billing\Gateway
 * @version $Id$
 */
abstract class Base 
{
	/**
	 * Default currency
	 * 
	 * @var string
	 */
	protected $_default_currency = 'USD';

	/**
	 * Money format
	 * 
	 * @var string 
	 */
	protected $_money_format = 'dollars';

	/**
	 * Array of options
	 * 
	 * @var array 
	 */
	protected $_options = array();

	/**
	 * Mode to run gateway in
	 * 
	 * @var string 
	 */
	public static $gateway_mode = null;

//- Public
	/**
	 * Instatiate new Gateay
	 * 
	 * @param array $options array of options
	 */
	public function __construct(array $options = array())
	{
		$this->_options = $options;
	}

	/**
	 * Formats money for gateway
	 * 
	 * @param type $money number to format 
	 * @return int,float fomratted money
	 */
	public function amount($money)
	{
		return $this->_money_format == 'cents' ? $money * 100 : sprintf('%.2f', $money);
	}

	/**
	 * Determines if we are in test mode
	 * 
	 * @return boolean true if test, false otherwise
	 */
	public function inTestMode()
	{
		return $this->gatewayMode() == 'test';
	}

	/**
	 * Sets or returns gateway mode
	 * 
	 * @param string $mode set mode
	 * @return string mode, if $mode is null 
	 */
	public function gatewayMode($mode = null)
	{
		if(!is_null($mode))
			static::$gateway_mode = $mode;
		else
			return static::$gateway_mode;
	}

	/**
	 * Communicate with endpoint via HTTP GET request
	 * 
	 * @param P3\Net\HTTP\Request,string $endpoint enpoint URL
	 * @param array $headers array of headers to include in the request
	 * 
	 * @return P3\Net\HTTP\Response
	 */
	public function sslGet($endpoint, array $headers = array())
	{
		return $this->_ssl_request('get', $endpoint, $data, $headers);
	}

	/**
	 * Communicate with endpoint via HTTP POST
	 * 
	 * @param P3\Net\HTTP\Request,string $endpoint enpoint URL
	 * @param string $data Data to post
	 * @param array $headers array of headers to include in request
	 * 
	 * @return P3\Net\HTTP\Response 
	 */
	public function sslPost($endpoint, $data, array $headers = array())
	{
		return $this->_ssl_request('post', $endpoint, $data, $headers);
	}

//- Protected
	/**
	 * Tests to make sure params exist in $hash
	 * 
	 * @param array $hash hash to check
	 * @param array $params required parameters
	 * 
	 * @throws P3\Merchant\Exception\ArgumentError
	 * 
	 * @return void
	 */
	protected function _requires($hash, array $params = array(), $strict_as_hell = false)
	{
		if(($c = array_diff($params, array_keys($hash))) && count($c))
			throw new \P3\Merchant\Exception\ArgumentError("Missing required parameter: %s", array(current($c)), 500);

		if($strict_as_hell && ($c = array_diff(array_keys($hash), $params)) && count($c))
			throw new \P3\Merchant\Exception\ArgumentError("Unkown parameter: %s", array(current($c)), 500);
	}

	/**
	 * Communicate with connection via GET or POST
	 * 
	 * @param string $method HTTP method (GET or POST)
	 * @param P3\Net\HTTP\Request,string $endpoint Endpoint url
	 * @param string $data data to post (POST only)
	 * @param array $headers array of headers to include with request
	 * 
	 * @return P3\Net\HTTP\Response 
	 */
	protected function _ssl_request($method, $endpoint, $data, array $headers = array())
	{
		$connection = new Connection($endpoint);
		return $connection->request($method, $data, $headers);
	}
}

?>