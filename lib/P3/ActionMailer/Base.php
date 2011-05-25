<?php

namespace P3\ActionMailer;
use       P3\Mail\Message;
use       P3\Mail\Message\Part as MessagePart;

/**
 * This is the class to extend your base mailers from.  Mailers work just as
 * controllers do.  Variables assigned with $this->{$var} will be passed to the
 * mailer template(s).  ActionMailer will look for 2 templates upon rendering, unless
 * a body was assigned within the action.  These templates are, 
 * {action}.text.plain.tpl and {action}.text.html.tpl.  You can have one or the other,
 * or even both.  If both templates exist, a MIME Multipart/alternative email is
 * sent.
 * 
 * Additionally, you can attach one or more files to the email within the action.
 * If attachments are set, ActionMailer uses MIME mutlipart/mixed to attach them 
 * to the email.  This also works with Multipart/alternative emails.
 * 
 * @see attach
 * 
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActionMailer
 * @version $Id$
 */
abstract class Base extends \P3\ActionController\Base
{
//- attr-private
	/**
	 * Attachments to be included in the outgoing message
	 * 
	 * @see attach
	 * @var array 
	 */
	private $_attachments = array();

	protected $_send_handler = \P3\Mail\SEND_HANDLER_P3;
	protected $_flags = 0;

//- Public
	/**
	 * Attaches file to email.
	 * 
	 * @see P3\Mail\Attachment::__construct()
	 * @param type $filepath full path to file attachment
	 * @param array $options options for P3\Mail\Attachment
	 * @return void
	 */
	public function attach($filepath, array $options = array())
	{
		$this->_attachments[] = new \P3\Mail\Attachment($filepath, $options);
	}

	/**
	 * Processes ActionMailer action and returns a new instance of P3\Mail\Message,
	 * or FALSE on failure
	 * 
	 * @param type $action action to call
	 * @param array $arguments arguments to be sent to action
	 * @return P3\Mail\Message message object 
	 */
	public function process($action, array $arguments = array())
	{
		$this->_init();
		call_user_func_array(array($this, $action), $arguments);

		$options    = array();
		$mime_parts = array();

		if(!is_null($this->from))
			$options['from'] = $this->from;

		if($this->templateExists($action.'.text.html'))
			$mime_parts[] = new MessagePart\HTML($this->render($action.'.text.html'));

		if($this->templateExists($action.'.text.plain'))
			$mime_parts[] = new MessagePart\Plain($this->render($action.'.text.plain'));

		if(count($this->_attachments))
			$options['attachments'] = $this->_attachments;

		if($this->cc)
			$options['cc'] = $this->cc;

		if($this->bcc)
			$options['bcc'] = $this->bcc;

		if(!count($mime_parts)) {
			if(!empty($this->body)) {
				$mime_parts[] = new MessagePart\Plain($this->body);
			} else {
				throw new \P3\Exception\ActionMailerException('No content was assigned to the mail Message: %s', array($action), 500);
			}
		}

		$options['flags'] = $this->_flags;
		$options['send_handler'] = $this->_send_handler;


		if(count($mime_parts))
			return new Message($this->to, $this->subject, $mime_parts, $options);

		return false;
	}

	/**
	 * Renders view and returns result
	 * 
	 * @param string $path path to view.  Current action is used if null
	 * @return string rendered view
	 */
	public function render($path = null)
	{
		return $this->_view->render($path);
	}

	/**
	 * Determines if view file exists
	 * 
	 * @param type string path to view
	 * @return type boolean
	 */
	public function templateExists($path)
	{
		return file_exists($this->_view->viewPath($path));
	}

//- Protected
	/**
	 * Prepares and returns view
	 * 
	 * @return type P3\Template\Base
	 */
	protected function _prepareView()
	{
		/* Define a new instance of our template class */
		if (isset($this->_attributes[self::ATTR_TEMPLATE_CLASS])) {
			$c = $this->getAttribute(self::ATTR_TEMPLATE_CLASS);
			$this->_view = new $c($this->_route);
		} else {
			$this->_view = new \P3\Template\Base(null, \str::fromCamelCase(get_class($this)).'/');
		}
		return $this->_view;
	}

//- Magic
	/**
	 * This method is used for the static calls of [Mailer]::deliver_[message],
	 * and [Mailer]::create_[message]
	 *
	 * @param string $name function being called on class
	 * @param array $arguments arguments that were passed to function call
	 * @return mixed true/false if deliver_*, message or false for create_*
	 * @magic
	 */
	public static function  __callStatic($function, $arguments)
	{
		$deliver = false;

		if(substr($function, 0, 8) == 'deliver_') {
			$action = substr($function, 8);
			$deliver = true;
		} elseif(substr($function, 0, 7) == 'create_') {
			$action = substr($function, 7);
			$deliver = false;
		} else {
			/* Otherwise pass it on */
			return call_user_func_array(array(current(class_parents(get_called_class())), $function), $arguments);
		}

		$mailer = new static;
		$message = $mailer->process($action, $arguments);

		if(!$message)
			throw new \P3\Exception\ActionMailerException("No Message was returned from process()");

		return $deliver ? $message->deliver() : $message;
	}
}

?>