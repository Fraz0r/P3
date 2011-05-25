<?php

namespace P3\Mail\Message\Delivery;

/**
 * Description of PEAR
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class PEAR implements IDelivers
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

		$headers = $this->_parseHeaders($message->headers(false));

		$pear_obj = \Mail::factory($message->flags & \P3\Mail\FLAG_SEND_USING_SMTP ? 'smtp' : 'mail', \P3\Mail::$SMTP);
		return $pear_obj->send($this->_to, $headers, $message->body);
	}

	private function _parseHeaders(array $headers)
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