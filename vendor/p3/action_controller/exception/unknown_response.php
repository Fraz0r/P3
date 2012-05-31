<?php

namespace P3\ActionController\Exception;

/**
 * Description of no_render
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class UnknownResponse extends \P3\Exception\ActionControllerException
{
	public function __construct($response, $action, $format)
	{
		parent::__construct('Unkown response: %s, given for action: %s (%s)', array(serialize($response), $action, $format), 500);
	}
}

?>