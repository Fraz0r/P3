<?php

namespace P3\Net\Http;

/**
 * OOP interface for parse_url with added support for domain and subdomain
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Net\Http
 * @version $Id$
 * @see parse_url()
 */
class Request implements \P3\Routing\IFace\Segmentable
{
	/**
	 * Singleton
	 * 
	 * @var P3\Routed\Request
	 */
	private static $_instance = null;

	/**
	 * Components returned by parse_url (with added subdomain and domain)
	 * 
	 * @var array
	 */
	private $_components = array();

	private $_method;
	private $_segments;

//- Public
	/**
	 * Intantiates new Request
	 * 
	 * This should never be called! (unless you are parsing a URL other than currentURL)
	 * 
	 * Use ::singleton(), or the more convienient `\P3::request()` method
	 * 
	 * @param string $url Optional url to use in parsing, currentURL is used if null
	 * @see currentURL
	 */
	public function __construct($url = null) {
		$url = is_null($url) ? self::currentURL() : $url;
		$this->_url = $url;

		$this->_parse();
	}

	/**
	 * Returns component of url, or null if nonexistent
	 * 
	 * @param type $component component to retreive
	 * @return mixed value of component
	 * @seee parse_url()
	 */
	public function component($component) {
		return isset($this->_components[$component]) ? $this->_components[$component] : null;
	}

	/**
	 * Returns domain name for URL
	 * 
	 * @return string domain name
	 */
	public function domain() {
		if (!isset($this->_components['domain']))
			$this->_parseDomain();

		return $this->component('domain');
	}

	/**
	 * Returns array of components
	 * 
	 * @return array
	 * @see parse_url()
	 */
	public function export() {
		return $this->_components;
	}

	public function format()
	{
		$base = basename($this->path());
		$ex   = explode('.', $base, 2);

		return count($ex) > 1 && !empty($ex[1]) ? $ex[1] : \P3::config()->routing->default_format;
	}

	/**
	 * Retrieves fragment component
	 * 
	 * @return string fragment component
	 * @see parse_url()
	 */
	public function fragment() {
		return $this->component('fragment');
	}

	/**
	 * Retrieves host component
	 * 
	 * @return string host component
	 * @see parse_url()
	 */
	public function host() {
		return $this->component('host');
	}

	public function is_xhr()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
					&& !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
					&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}

	public function method()
	{
		if(!isset($this->_method))
			if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['_method']))
				$this->_method = strtolower($_POST['_method']);
			else
				$this->_method = strtolower($_SERVER['REQUEST_METHOD']);
			
		return $this->_method;
	}

	/**
	 * Retrieves pass component
	 * 
	 * @return string pass component
	 * @see parse_url()
	 */
	public function pass() {
		return $this->component('pass');
	}

	/**
	 * Retrieves path component
	 * 
	 * @return string path component
	 * @see parse_url()
	 */
	public function path() {
		return $this->component('path');
	}

	public function port() {
		$port = $this->component('port');

		if (is_null($port))
			$port = $this->protocol() == 'https' ? 443 : 80;

		return $port;
	}

	/**
	 * Retrieves protocol component
	 * 
	 * @return string protocol component
	 * @see parse_url()
	 */
	public function protocol() {
		return $this->component('scheme');
	}

	/**
	 * Retrieves query component
	 * 
	 * @return string query component
	 * @see parse_url()
	 */
	public function query() {
		return $this->component('query');
	}

	/**
	 * Retrieves user component
	 * 
	 * @return string user component
	 * @see parse_url()
	 */
	public function user() {
		return $this->component('user');
	}

	/**
	 * TODO: Should be moved to trait (aka "Module" in P3)
	 */
	public function segments()
	{
		if(!isset($this->_segments))
			$this->_segments = \P3\Routing\Route\Segment::get_from_path($this->path());

		return $this->_segments;
	}

	/**
	 * Retrieves subdomain component
	 * 
	 * @return string subdomain component
	 * @see _parseDomain
	 */
	public function subdomain() {
		if (!isset($this->_components['subdomain']))
			$this->_parseDomain();

		$subdomain = $this->component('subdomain');

		return empty($subdomain) ? null : $subdomain;
	}

//- Private
	/**
	 * Calls parse_url on $this->_url, storing components (only called once)
	 * 
	 * @return void
	 * @see parse_url()
	 */
	private function _parse() {
		$this->_components = parse_url($this->_url);
		$this->_components['path'] = trim($this->_components['path'], '/');
	}

	/**
	 * Parses domain AND subdomain of $this->_url (only called once [if needed])
	 * 
	 * return void
	 */
	private function _parseDomain() {
		$parts = explode('.', $this->component('host'));
		$ext = array_pop($parts);
		$domain = array_pop($parts);

		$this->_components['domain'] = $domain . '.' . $ext;
		$this->_components['subdomain'] = count($parts) ? implode('.', $parts) : null;
	}

	/**
	 * Singleton accessor
	 * 
	 * @return P3\Request singleton
	 */
	public static function singleton() {
		if (is_null(self::$_instance))
			self::$_instance = new self;

		return self::$_instance;
	}

	/**
	 * Builds and return string for the current URL
	 * 
	 * @return string
	 */
	public static function currentURL() {
		$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];
		$port = $_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT'];

		return $protocol . $host . $port . $uri;
	}

//- Magic
	public function __get($var)
	{
		// TODO: Need to throw exception here
		return $_GET[$var];
	}

}
?>