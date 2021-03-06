<?php

namespace P3;
use P3\Exception\SessionException as Error;

/**
 * Description of session
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Session extends \ArrayObject
{
	/**
	 * @var array Holds ojbect attributes
	 */
	protected $_attributes = array();

	/**
	 * @var P3\Session\Base Singleton container
	 */
	static protected $_instance;

	/**
	 * @var boolean Is a handler set?
	 */
	static protected $_handlerSet = false;

	/**
	 * @var boolean Used to determine if a function originated internally
	 */
	static protected $_startedInternally = false;

	/**
	 * Cannot be called via standard `new Class` conventions.  Must be started via ::start()
	 */
	public function __construct()
	{
		if(!self::$_startedInternally)
			throw new Error("P3 Session's constructor must be called via ::start()");
		self::$_startedInternally = false;

		if(!self::$_handlerSet)
			self::registerHandler(new Session\Handler\File());

		session_start();

		parent::__construct($_SESSION, \ArrayObject::ARRAY_AS_PROPS);

		$_SESSION = $this;
	}

	/**
	 * Ends Session
	 *
	 * @return void
	 */
	public function  __destruct()
	{
		$this->close();
	}

	/**
	 * Closes Session
	 *
	 * @return void
	 */
	public function close()
	{
		$_SESSION = $this->getArrayCopy();
		session_write_close();
		$_SESSION = $this;
	}

	public function flash($var, $val = null)
	{
		if(is_null($val)) {
			if(isset($this['flash']) && isset($this['flash'][$var])) {
				$ret = $this['flash'][$var];
				$this['flash'][$var] = null;
				return $ret;
			} else return false;
		} else {
			if(!isset($this['flash']))
				$this['flash'] = array();

			$this['flash'][$var] = $val;
		}
	}

	/**
	 * Retreives Object Attribute
	 *
	 * @param int $attr Attribute to retrieve
	 * @return mixed Value
	 */
	public function getAttribute($attr)
	{
		return isset($this->_attributes[$attr]) ? $this->_attributes[$attr] : null;
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

	/**
	 * Registers Handler Object into Session
	 *
	 * @param object $handler Session Handler Object
	 */
	public static function registerHandler($handler)
	{
		if(isset($_SESSION)) throw new Error("Handlers must be set before the session is started");

		static::$_handlerSet = true;

		\session_set_save_handler(
			array($handler, 'open'),
			array($handler, 'close'),
			array($handler, 'read'),
			array($handler, 'write'),
			array($handler, 'destroy'),
			array($handler, 'gc')
		);
	}

	/**
	 * Sets Object Attribute
	 *
	 * @param int $attr Attribute to set
	 * @param mixed $val Desired value for Attribute
	 */
	public static function setAttribute($attr, $val)
	{
		switch($attr) {
			default:
				$this->_attributes[$attr] = $val;
		}
	}

	/**
	 * Returns Singleton for class
	 *
	 * @return P3\Session\Base Singleton object
	 */
	public static function singleton()
	{
		if(empty(self::$_instance))
			throw new Error("Session is not started, call ::start() first.");

		return self::$_instance;
	}

	/**
	 * Starts Session
	 *
	 * @return void
	 */
	public static function start($name = null)
	{
		if(isset($_SESSION))
			throw new Error("Session is already started.");

		if(!is_null($name))
			session_name($name);

		self::$_startedInternally = true;
		self::$_instance = new static;

		self::singleton();
	}


//Private
}

?>