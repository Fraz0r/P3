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
		return mail($message->to, $message->subject, $message->body, $message->headers());
	}
}

?>