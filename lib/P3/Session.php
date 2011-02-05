<?php
/**
 * Description of Session
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */

namespace P3;

class Session extends \ArrayObject
{
	static protected $instance;

	public function __construct()
	{
		session_start();

		parent::__construct($_SESSION, \ArrayObject::ARRAY_AS_PROPS);
		$_SESSION = $this;
	}

	public function  __destruct()
	{
		$this->close();
	}

	public function close()
	{
		$_SESSION = $this->getArrayCopy();
		session_write_close();
		$_SESSION = $this;
	}

	/**
	 * Returns whether or not the Session is active
	 *
	 * @todo Fix isActive
	 */
	public function isActive()
	{
		return true;
	}
	public static function singleton()
	{
		if(empty(self::$instance)) {
			self::$instance = new Session;
		}

		return self::$instance;
	}

	public static function start()
	{
		self::singleton();
	}
}
?>