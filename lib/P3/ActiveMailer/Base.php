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
	const ATTR_MAIL_TYPE = 0;
	const MAIL_TYPE_PLAINTEXT = 0;
	const MAIL_TYPE_HTML      = 1;

	/* Attributes */

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

	public static function getAttr($attr)
	{
		switch($attr) {
			case self::ATTR_MAIL_TYPE:
				return (isset(static::$_attrs[$attr]) ? static::$_attrs[$attr] : self::MAIL_TYPE_HTML);
			default:
				return (isset(static::$_attrs[$attr]) ? static::$_attrs[$attr] : null);
		}
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
		static::$_view    = new \P3\Template\Base;
	}

	public static function setAttr($attr, $val)
	{
		static::$_attrs[$attr] = $val;
	}

//Protected Static
	protected static function _addHeader($header)
	{
		static::$_headers .= $header."\r\n";
	}

	protected static function _deliver($msg, $vars = null)
	{
		static::_prepareMail($msg, $vars);

		foreach(static::$_recipients as $to) {
			mail($to, static::$_subject, static::$_body, static::$_headers);
		}
	}

	protected static function _prepareMail($msg, $vars = null)
	{
		static::reset();
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

	protected static function _render($msg, array $options = array())
	{
		$path = 'notifier/'.$msg;
		$path .= (static::getAttr(self::ATTR_MAIL_TYPE) == self::MAIL_TYPE_HTML) ? '.html' : '.txt';
		$path .= '.tpl';

		$template = static::$_view;
		$template->setLayout(static::$_layout);
		return $template->render($path);
	}


//Magic
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