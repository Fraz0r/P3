<?php

namespace P3\ActionMailer\Exception;

/**
 * Description of no_content
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoContent extends \P3\Exception\ActionMailerException
{
	public function __construct($action)
	{
		parent::__construct('No content was assigned to the mail message \'%s\'', [$action]);
	}
}

?>