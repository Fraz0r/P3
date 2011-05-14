<?php

namespace P3\ActionMailer;
use       P3\Mail\Message;
use       P3\Mail\Message\Part as MessagePart;

/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Base extends \P3\ActionController\Base
{
	private $_attachments = array();

//- Public
	public function attach($filepath, array $options = array())
	{
		$this->_attachments[] = new \P3\Mail\Attachment($filepath, $options);
	}

	public function process($action, array $arguments = array())
	{
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

		if(count($mime_parts))
			return new Message($this->to, $this->subject, $mime_parts, $options);

		return false;
	}

	public function render($path = null)
	{
		return $this->_view->render($path);
	}

	public function templateExists($path)
	{
		return file_exists($this->_view->viewPath($path));
	}

//- Protected
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

//- Static

//- Magic
	/**
	 * Forwards any deliver_<msg> to _deliver method
	 *
	 * @param string $name Function being called on class
	 * @param array $arguments Arguments that were passed to function call
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

		if(isset($action)) {
			$mailer = new static;
			$message = $mailer->process($action, $arguments);
		}

		return $deliver ? $message->deliver() : $message;
	}
}

?>