<?php

namespace P3\Routed;

/**
 * Description of Request
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Request 
{
	private static $_instance = null;

	private $_method     = null;
	private $_components = array();

	public function __construct($url = null)
	{
		$url = is_null($url) ? self::currentURL() : $url;
		$this->_url = $url;

		$this->_parse();
	}

	public function component($component)
	{
		return isset($this->_components[$component]) ? $this->_components[$component] : null;
	}

	public function domain()
	{
		if(!isset($this->_components['domain']))
			$this->_parseDomain();

		return $this->component('domain');
	}

	public function export()
	{
		return $this->_components;
	}

	public function fragment()
	{
		return $this->component('fragment');
	}

	public function host()
	{
		return $this->component('host');
	}

	public function pass()
	{
		return $this->component('pass');
	}

	public function path()
	{
		return $this->component('path');
	}

	public function protocol()
	{
		return $this->component('scheme');
	}

	public function query()
	{
		return $this->component('query');
	}

	public function user()
	{
		return $this->component('user');
	}

	public function subdomain()
	{
		if(!isset($this->_components['subdomain']))
			$this->_parseDomain();

		$subdomain = $this->component('subdomain');

		return empty($subdomain) ? null : $subdomain;
	}

	private function _parse()
	{
		$this->_components = parse_url($this->_url);
	}

	private function _parseDomain()
	{
		$parts = explode('.', $this->component('host'));
		$ext = array_pop($parts);
		$domain = array_pop($parts);
		$this->_components['domain'] = $domain.'.'.$ext;
		$this->_components['subdomain'] = implode('.', $parts);
	}

	public static function singleton()
	{
		if(is_null(self::$_instance))
			self::$_instance = new self;

		return self::$_instance;
	}

	public static function currentURL()
	{
		$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$host     = $_SERVER['HTTP_HOST'];
		$uri      = $_SERVER['REQUEST_URI'];
		$port     = $_SERVER['SERVER_PORT'] == '80' ? '' : ':'.$_SERVER['SERVER_PORT'];

		return $protocol.$host.$port.$uri;
	}
}

?>