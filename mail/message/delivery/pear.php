<?php

namespace P3\Mail\Message\Delivery;
use P3\Mail\Message;

/**
 * Description of PEAR
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class PEAR implements IFace\Delivers
{
	private $_message = null;
	private $_to  = null;

	public function deliver($message)
	{
		$this->_message = $message;

		if(FALSE === (@include_once("Mail.php")))
			throw new \P3\Exception\MailMessageException("PEAR Mail package required to be in global include path");

		if(FALSE === (@include_once("Net/SMTP.php")))
			throw new \P3\Exception\MailMessageException("PEAR Net/SMPT package required to be in global include path");

		$headers = $this->_parse_headers($message->headers(false));

		if($message->flags & Message::FLAG_SEND_VIA_SMTP)
			$pear_obj = \Mail::factory('smtp', \P3::config()->mail->delivery->smtp);
		else
			$pear_obj = \Mail::factory('mail');

		$return = $pear_obj->send($this->_to, $headers, $message->body);

		if(is_a($return, 'PEAR_Error'))
			throw new Exception\PearError($return);

		return $return;
	}

	private function _parse_headers(array $headers)
	{
		$new = array();

		$new['To'] = $this->_message->to;
		$new['Subject'] = $this->_message->subject;
		foreach($headers as $h) {
			list($n, $v) = explode(': ', $h, 2);
			$new[$n] = $v;
		}

		/* PEAR handles BCC/CC diff */
		$this->_to = $this->_message->to;
		if(isset($new['CC']))
			$this->_to .= ', '.$new['CC'];

		if(isset($new['BCC']))
			$this->_to .= ', '.$new['BCC'];

		return $new;
	}
}

?>