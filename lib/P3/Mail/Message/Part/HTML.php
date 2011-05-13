<?php

namespace P3\Mail\Message\Part;

/**
 * Description of HTML
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class HTML extends \P3\Mail\Message\Part
{
	public function __construct($contents, array $options = array())
	{
		$options['content_type'] = 'text/html';
		parent::__construct($contents, $options);
	}
}

?>