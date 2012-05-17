<?php

namespace P3\Template\Exception;

/**
 * Description of unknown_buffer
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class UnknownBuffer extends \P3\Exception\TemplateException
{
	public function __construct($file, $buffer_name)
	{
		parent::__construct('%s attempted to yield() unknown buffer: %s', array($file, $buffer_name));
	}
}

?>