<?php

namespace P3\Merchant\Billing\Gateway;

/**
 * Description of Gateway
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base 
{
	protected $_default_currency = 'USD';
	protected $_money_format = 'dollars';
	protected $_options = array();

	public static $gateway_mode = null;

//- Public
	public function __construct(array $options = array())
	{
		$this->_options = $options;
	}

	public function amount($money)
	{
		return $this->_money_format == 'cents' ? $money : sprintf('%.2f', $money);
	}

	public function gatewayMode($mode = null)
	{
		if(!is_null($mode))
			static::$gateway_mode = $mode;
		else
			return static::$gateway_mode;
	}

	public function sslGet($endpoint, $data, array $headers = array())
	{
		return $this->_ssl_request('get', $endpoint, $data, $headers);
	}

	public function sslPost($endpoint, $data, array $headers = array())
	{
		return $this->_ssl_request('post', $endpoint, $data, $headers);
	}

//- Protected
	protected function _requires($hash, array $params = array())
	{
		foreach($params as $param) {
			if(!isset($hash[$param]))
				throw new \P3\Merchant\Exception\ArgumentError("Missing required parameter: %s", array($param), 500);
		}
	}

	protected function _ssl_request($method, $endpoint, $data, array $headers = array())
	{
		$connection = new Connection($endpoint);
		return $connection->request($method, $data, $headers);
	}
}

?>