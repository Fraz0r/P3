<?php

/**
 * Description of Mailer
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\ActiveMailer;

class Base extends \P3\Model\Base
{
	/* Attributes */
	const ATTR_MAIL_TYPE = 0;

	const MAIL_TYPE_PLAINTEXT = 0;
	const MAIL_TYPE_HTML      = 1;

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
	public static function addHeader($header)
	{
		static::$_headers .= $header."\r\n";
	}

	public static function deliver($msg, $vars = null)
	{
		static::reset();
		$ret = is_null($vars) ? static::$msg() : static::$msg($vars);

		if(is_array(static::$_body)) {
			static::$_view->assign(static::$_body);
			static::$_body = null;
		}

		static::$_recipients = !is_array(static::$_recipients) ? explode(',', static::$_recipients) : static::$_recipients;
		static::$_body = empty(static::$_body) && $ret !== FALSE ? static::render($msg) : static::$_body;

		if(static::getAttr(self::ATTR_MAIL_TYPE) == self::MAIL_TYPE_HTML) {
			static::addHeader("MIME-Version: 1.0");
			static::addHeader("Content-type: text/html; charset=iso-8859-1");
		}

		if(!empty(static::$_from)) {
			static::addHeader("From: ".static::$_from);
		}

		foreach(static::$_recipients as $to) {
			mail($to, static::$_subject, static::$_body, static::$_headers);
		}
	}

	public static function getAttr($attr)
	{
		switch($attr) {
			case ATTR_MAIL_TYPE:
				return (isset(static::$_attrs[$attr]) ? static::$_attrs[$attr] : self::MAIL_TYPE_HTML);
			default:
				return (isset(static::$_attrs[$attr]) ? static::$_attrs[$attr] : null);
		}
	}

	public static function render($msg, array $options = array())
	{
		$path = 'notifier/'.$msg;
		$path .= (static::getAttr(self::ATTR_MAIL_TYPE) == self::MAIL_TYPE_HTML) ? '.html' : '.txt';
		$path .= '.tpl';

		$template = static::$_view;
		$template->setLayout(static::$_layout);
		return $template->render($path);
	}

	public static function reset()
	{
		static::$_recipients = null;
		static::$_from       = null;
		static::$_subject    = null;
		static::$_body       = null;

		static::$_attrs    = array();
		static::$_routing_data = Router::parseRoute();
		static::$_args    = static::$_routing_data['args'];
		static::$_view    = new Template;
	}

	public static function setAttr($attr, $val)
	{
		static::$_attrs[$attr] = $val;
	}


//Magic
	public static function  __callStatic($name, $arguments)
	{
		if(FALSE !== strpos($name, 'deliver_')) {
			$func = str_replace('deliver_', '', $name);

			if(!count($arguments)) {
				static::deliver($func);
			} else {
				static::deliver($func, $arguments[0]);
			}
		} else {
			static::$name();
		}
	}
}
?>