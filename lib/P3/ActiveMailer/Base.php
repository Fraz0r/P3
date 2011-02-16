<?php

/**
 * Description of Mailer
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\ActiveMailer;


use P3\Router as Router;

class Base extends \P3\Model\Base
{
	/* Attributes */
	const ATTR_MAIL_TYPE = 0;
	const MAIL_TYPE_PLAINTEXT = 0;
	const MAIL_TYPE_HTML      = 1;


	/**
	 * @var array Container for class attributes
	 */
	protected static $_attrs = array();

	/* Mail Vars */
	protected static $_recipients;
	protected static $_from;
	protected static $_subject;
	protected static $_body;
	protected static $_layout = null;

	protected static $_headers = '';
	protected static $_routing_data = null;
	protected static $_args = null;
	protected static $_view = null;

//Static

	/**
	 * Retrieves class Attribute
	 *
	 * @param int $attr Attribute to retrieve
	 * @return mixed Class attribute value
	 */
	public static function getAttr($attr)
	{
		switch($attr) {
			case self::ATTR_MAIL_TYPE:
				return (isset(static::$_attrs[$attr]) ? static::$_attrs[$attr] : self::MAIL_TYPE_HTML);
			default:
				return (isset(static::$_attrs[$attr]) ? static::$_attrs[$attr] : null);
		}
	}


	/**
	 * Sets class Attribute
	 *
	 * @param int $attr Attribute to set
	 * @param mixed $val Value to set attribute to
	 */
	public static function setAttr($attr, $val)
	{
		static::$_attrs[$attr] = $val;
	}

//Protected Static
	/**
	 * Add Header to Email
	 *
	 * @param string $header Adds header row to email
	 */
	protected static function _addHeader($header)
	{
		static::$_headers .= $header."\r\n";
	}

	/**
	 * Prepares and sends email
	 *
	 * @param string $msg Action to call on extending ActiveMailer class
	 * @param array $vars Variables to pass to Mailer Action (if any)
	 */
	protected static function _deliver($msg, $vars = null)
	{
		static::_prepareMail($msg, $vars);

		foreach(static::$_recipients as $to) {
			mail($to, static::$_subject, static::$_body, static::$_headers);
		}
	}

	/**
	 * Prepares email message for sending
	 *
	 * @param string $msg Action to call on extending ActiveMailer class
	 * @param array $vars Variables to pass to ActiveMailer action (if any)
	 */
	protected static function _prepareMail($msg, $vars = null)
	{
		static::_reset();
		$ret = is_null($vars) ? static::$msg() : static::$msg($vars);

		if(is_array(static::$_body)) {
			static::$_view->assign(static::$_body);
			static::$_body = null;
		}

		static::$_recipients = !is_array(static::$_recipients) ? explode(',', static::$_recipients) : static::$_recipients;
		static::$_body = empty(static::$_body) && $ret !== FALSE ? static::_render($msg) : static::$_body;

		if(static::getAttr(self::ATTR_MAIL_TYPE) == self::MAIL_TYPE_HTML) {
			static::_addHeader("MIME-Version: 1.0");
			static::_addHeader("Content-type: text/html; charset=iso-8859-1");
		}

		if(!empty(static::$_from)) {
			static::_addHeader("From: ".static::$_from);
		}
	}

	/**
	 * Renders body for email, using template
	 * 
	 * @param string $msg Action being called on extending ActiveMailer class (used to find template file)
	 * @param array $options Options
	 * @return string Body for email
	 */
	protected static function _render($msg, array $options = array())
	{
		$path = 'notifier/'.$msg;
		$path .= (static::getAttr(self::ATTR_MAIL_TYPE) == self::MAIL_TYPE_HTML) ? '.html' : '.txt';
		$path .= '.tpl';

		$template = static::$_view;
		$template->setLayout(static::$_layout);
		return $template->render($path);
	}

	/**
	 * Resets class for another mail message
	 *
	 * NOTE:  This does NOT reset attributes
	 *
	 * @return void
	 */
	protected static function _reset()
	{
		static::$_recipients = null;
		static::$_from       = null;
		static::$_subject    = null;
		static::$_body       = null;

		static::$_attrs    = array();
		static::$_routing_data = Router::parseRoute();
		static::$_args    = static::$_routing_data['args'];
		static::$_view    = new \P3\Template\Base;
	}


//Magic
	/**
	 * Forwards any deliver_<msg> to _deliver method
	 *
	 * @param string $name Function being called on class
	 * @param array $arguments Arguments that were passed to function call
	 * @magic
	 */
	public static function  __callStatic($name, $arguments)
	{
		if(FALSE !== strpos($name, 'deliver_')) {
			$func = str_replace('deliver_', '', $name);

			if(!count($arguments)) {
				static::_deliver($func);
			} else {
				static::_deliver($func, $arguments[0]);
			}
		} else {
			static::$name();
		}
	}
}
?>