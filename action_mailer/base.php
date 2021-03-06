<?php

namespace P3\ActionMailer;
use       P3\ActionController\ActionView;
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

	protected $_send_handler;
	protected $_flags = 0;

	public function __construct($send_handler = null)
	{
		if(is_null($send_handler))
			$send_handler = \P3::config()->action_mailer->delivery_handler;

		$this->_send_handler = $send_handler;
	}

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
		$this->_before_filter();
		call_user_func_array(array($this, $action), $arguments);

		$options    = array();
		$mime_parts = array();

		if(!is_null($this->from))
			$options['from'] = $this->from;


		$base = ActionView::get_controller_base($this);
		
		$template = $action.'.text.html';
		if(ActionView::is_readable($base.'/'.$template))
			$mime_parts[] = new MessagePart\HTML($this->render($template, ['format' => 'html']));
				
		$template = $action.'.text.plain';
		if(ActionView::is_readable($base.'/'.$template))
			$mime_parts[] = new MessagePart\Plain($this->render($template, ['format' => 'plain']));

		if(count($this->_attachments))
			$options['attachments'] = $this->_attachments;

		if($this->var_exists('cc'))
			$options['cc'] = $this->cc;

		if($this->var_exists('bcc'))
			$options['bcc'] = $this->bcc;

		if(!count($mime_parts)) {
			if($this->var_exists('body'))
				$mime_parts[] = new MessagePart\Plain($this->body);
			elseif(ActionView::is_readable($base.'/'.$action))
				$mime_parts[] = new MessagePart\Plain($this->render($action));
			else
				throw new Exception\NoContent($action);
		}

		$options['flags'] = $this->_flags;
		$options['send_handler'] = $this->_send_handler;


		if(count($mime_parts))
			return new Message($this->to, $this->subject, $mime_parts, $options);

		$this->_after_filter();

		return false;
	}

	/**
	 * Renders view and returns result
	 * 
	 * @param string $path path to view.  Current action is used if null
	 * @return string rendered view
	 */
	public function render($path, array $options = [])
	{
		$view = new MailerView($this, $path);

		$layout = isset($options['layout']) ? $options['layout'] : $this->_layout;
		
		if($layout)
			$view->init_layout($layout, $options['format']);
		

		return $view->render();
	}

//- Protected
	protected function _after_filter()
	{ }

	protected function _before_filter()
	{ }

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