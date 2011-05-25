<?php

namespace P3\Mail\Message\Delivery;

/**
 * Description of PEAR
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class PEAR implements iDelivers
{
	private $_message = null;

	public function deliver($message)
	{
		$this->_message = $message;

		if(FALSE === (@include_once("Mail.php")))
			throw new \P3\Exception\MailMessageException("PEAR delivery requires the PEAR Library to be in your include path");

		$pear_obj = \Mail::factory($message->flags & \P3\Mail\FLAG_SEND_USING_SMTP ? 'smtp' : 'mail', \P3\Mail::$SMTP);
		return $pear_obj->send($message->to, $this->_parseHeaders($message->headers(false)), $message->body);
	}

	private function _parseHeaders(array $headers)
	{
		$new = array();

		$new['To'] = $this->_message->to;
		$new['Subject'] = $this->_message->subject;
		foreach($headers as $h) {
			list($n, $v) = explode(':', $h, 2);
			$new[$n] = $v;
		}

		return $new;
	}
}

?>