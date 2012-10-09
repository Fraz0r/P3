<?php

namespace P3\Template\Exception;

/**
 * Description of unknown_render
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class UnknownRender extends \P3\Exception\TemplateException
{
	public function __construct()
	{
		parent::__construct('Couldn\'t understand the attempted render');
	}
}

?>