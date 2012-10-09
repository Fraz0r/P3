<?php

namespace P3\ActionController\Exception;

/**
 * Description of no_response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class NoResponse extends \P3\Exception\ActionControllerException
{
	public function __construct(array $info)
	{
		parent::__construct('No %s response given for %s#%s', array($info['format'], $info['controller'], $info['action']), 500);
	}
}

?>