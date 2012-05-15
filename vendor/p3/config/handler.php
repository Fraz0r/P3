<?php

namespace P3\Config;

require_once(\P3\PATH.'/config/section.php');
/**
 * Description of handler
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
final class Handler extends Section
{
	private $_plugins = array();
	private static $_instance;

	public function __construct()
	{
		parent::__construct('core');
	}

	public function plugin_enable($name)
	{
		$this->_plugins[$name] = P3\Plugin::register($name);
	}

//- Static
	public static function singleton()
	{
		if(!isset(self::$_instance))
			self::$_instance = new self;

		return self::$_instance;
	}
}

?>