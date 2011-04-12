<?php

namespace P3\ActionMailer;
use       P3\Mail\Message;

/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Base extends \P3\ActionController\Base
{

//- Public

//- Protected

//- Static

//- Magic
	/**
	 * Forwards any deliver_<msg> to _deliver method
	 *
	 * @param string $name Function being called on class
	 * @param array $arguments Arguments that were passed to function call
	 * @magic
	 */
	public static function  __callStatic($name, $arguments)
	{
		$deliver = false;

		if(FALSE !== strpos($name, 'deliver_')) {
			$name = str_replace('deliver_', '', $name);
			$deliver = true;
		}

	}
}

?>