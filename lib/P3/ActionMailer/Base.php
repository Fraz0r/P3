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

//- Public
	public function process($action, array $arguments = array())
	{
		call_user_func_array(array($this, $action), $arguments);

		$options    = array();
		$mime_parts = array();

		if($this->templateExists($action.'.text.plain'))
			$mime_parts[] = new MessagePart\Plain($this->render($action.'.text.plain'));

		if($this->templateExists($action.'.text.html'))
			$mime_parts[] = new MessagePart\HTML($this->render($action.'.text.html'));

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
	public static function  __callStatic($name, $arguments)
	{
		$deliver = false;

		if(FALSE !== strpos($name, 'deliver_')) {
			$name = str_replace('deliver_', '', $name);
			$deliver = true;
		} elseif(FALSE !== strpos($name, 'create_')) {
			$name = str_replace('deliver_', '', $name);
			$deliver = false;
		}

		if(isset($name)) {
			$mailer = new static;
			$message = $mailer->process($name, $arguments);
		}

		var_dump($message);

	}
}

?>