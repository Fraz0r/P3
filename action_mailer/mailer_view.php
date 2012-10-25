<?php

namespace P3\ActionMailer;
use P3\ActionController\Exception\LayoutInvalid;
use P3\Template\Layout;

/**
 * Description of mailer_view
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class MailerView extends \P3\ActionController\ActionView
{
	protected $_format;

	public function __construct($controller, $template = null, $format = null)
	{
		$this->_format     = $format;

		parent::__construct($controller, $template);
	}

	public function init_layout($layout_base, $format = null)
	{
		$format = isset($format) ? $format : $this->_format;

		$template = "{$layout_base}.text.{$format}.tpl";

		if(is_readable(self::base_path().'/layouts/'.$template)) {
			$this->set_layout(new Layout($template));
			return true;
		} else {
			return false;
		}
	}
}

?>