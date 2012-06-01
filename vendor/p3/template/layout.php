<?php

namespace P3\Template;

/**
 * Description of layout
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Layout extends Base
{
	public function __construct($path)
	{
		parent::__construct(\P3::config()->action_view->base_path.'/layouts/'.$path);
	}
}

?>