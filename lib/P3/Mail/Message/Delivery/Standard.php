<?php

namespace P3\Mail\Message\Delivery;

/**
 * Description of Standard
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Standard implements iDelivers
{
	public function deliver($message)
	{
		if($message->flags & \P3\Mail\FLAG_SEND_USING_SMTP)
			throw new \P3\Exception\MailMessageException("Sorry, but SMPT is not currently supported on P3s standard delivery interface.  Please see wiki for using PEAR w/ SMTP");

		return mail($message->to, $message->subject, $message->body, $message->headers());
	}
}

?>