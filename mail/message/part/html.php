<?php

namespace P3\Mail\Message\Part;

/**
 * MIME Type: "text/html" message part
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Mail\Message\Part
 * @version $Id$
 */
class HTML extends \P3\Mail\Message\Part
{
	/**
	 * Instantiates new Message\Part
	 * 
	 * @param string $contents contents
	 * @param array $options options
	 * @see P3\Mail\Message\Part::__construct()
	 */
	public function __construct($contents, array $options = array())
	{
		$options['content_type'] = 'text/html';
		parent::__construct($contents, $options);
	}
}

?>