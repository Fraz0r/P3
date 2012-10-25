<?php

namespace P3\Mail\Message\Delivery\Exception;

/**
 * Description of pear_error
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class PearError extends \P3\Exception\ActionMailerException
{
	public function __construct(\PEAR_Error $error)
	{
		parent::__construct('PEAR Failed: %s', [$error->message]);
	}
}

?>