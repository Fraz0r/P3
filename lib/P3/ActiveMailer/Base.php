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
	const MAIL_TYPE_BOTH      = 2;

	const ATTR_MULTIPART = 1;

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
	protected static $_route   = null;
	protected static $_args    = null;
	protected static $_view    = null;

	/* MIME/Multipart */
	protected static $_attachments  = array();
	protected static $_mixedBoundry = 'P3-mixed-';
	protected static $_altBoundry   = 'P3-alt-';
	protected static $_mimeHash     = null;

//Static

	/**
	 * Adds attachment to email
	 *
	 * @param string $file_path Full path to file attachment
	 */
	public static function addAttachment($file_path)
	{
		$contents = \file_get_contents($file_path);

		if(!$contents) return false;

		static::$_attachments[$file_path] = \chunk_split(\base64_encode($contents));
	}

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
			case self::ATTR_MULTIPART:
				return (isset(static::$_attrs[$attr]) ? static::$_attrs[$attr] : false);
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
		static::$_mimeHash = md5(date('r'), time());

		static::_addHeader("MIME-Version: 1.0");

		if(!empty(static::$_from)) {
			static::_addHeader("From: ".static::$_from);
		}

		switch(static::getAttr(self::ATTR_MAIL_TYPE)) {
			case self::MAIL_TYPE_HTML:
					break;
			case self::MAIL_TYPE_PLAINTEXT:
					break;
			case self::MAIL_TYPE_BOTH:
					break;
		}
	}

	/**
	 * Renders body for email, using template
	 * 
	 * @param string $msg Action being called on extending ActiveMailer class (used to find template file)
	 * @param array $options Options
	 * @return string Body for email
	 *
	 * @todo Need to look for .txt and .html layouts.  not just one
	 * @todo Need to add attahment mime types... just have application/zip as a placeholder for now
	 */
	protected static function _render($msg, array $options = array())
	{
		$rendered = '';
		$path     = 'notifier/'.$msg;
		$template = static::$_view;
		$template->setLayout(static::$_layout);

		if(count(static::$_attachments)) {
			$rendered .= "Content-Type: multipart/mixed; boundary=\"".static::$_mixedBoundry.static::$_mimeHash."\"\r\n";
			$rendered .= '--'.static::$_mixedBoundry.static::$_mimeHash."\r\n";
		}

		switch(static::getAttr(self::ATTR_MAIL_TYPE)) {
			case self::MAIL_TYPE_BOTH:

				$rendered .= "Content-Type: multipart/alternative; boundary=\"".static::$_altBoundry.static::$_mimeHash."\"\r\n";

				$html_path  = $path.'.html';
				$plain_path = $path.'.txt';

				$rendered .= '--'.static::$_altBoundry.static::$_mimeHash."\r\n";
				$rendered .= "Content-Type: text/plain; charset=\"iso-8859-1\r\n";
				$rendered .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$rendered .= $template->render($plain_path);

				$rendered .= '--'.static::$_altBoundry.static::$_mimeHash."\r\n";
				$rendered .= "Content-Type: text/html; charset=\"iso-8859-1\r\n";
				$rendered .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$rendered .= $template->render($html_path);

				$rendered .= '--'.static::$_altBoundry.static::$_mimeHash."--\r\n";
				break;

			case self::MAIL_TYPE_HTML:
				$path     .= '.html';
				$rendered .= "Content-Type: text/html; charset=\"iso-8859-1\r\n";
				$rendered .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$rendered .= $template->render($path);
				break;

			case self::MAIL_TYPE_PLAINTEXT:
				$path     .= '.txt';
				$rendered .= "Content-Type: text/plain; charset=\"iso-8859-1\r\n";
				$rendered .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$rendered .= $template->render($path);
				break;
		}

		if(count(static::$_attachments)) {
			foreach(static::$_attachments as $path => $attachment) {
				$rendered .= '--'.static::$_mixedBoundry.static::$_mimeHash."\r\n";
				$rendered .= "Content-Type: application/zip; name=\"".basename($path)."\r\n";
				$rendered .= "Content-Transfer-Encoding: base64\r\n";
				$rendered .= "Content-Disposition: attachment\r\n\r\n";
			}

			$rendered .= '--'.static::$_mixedBoundry.static::$_mimeHash."--\r\n";
		}

		return $rendered;
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
		static::$_route    = Router::getDispatched();
		static::$_args    = $_GET;
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