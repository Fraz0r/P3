<?php

namespace P3\Mail\Message\Part;

/**
 * Description of Plain
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Plain extends \P3\Mail\Message\Part 
{
	public function __construct($contents, array $options = array())
	{
		parent::__construct($contents, $options);
	}
}

?>
