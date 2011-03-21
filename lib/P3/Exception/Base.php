<?php

namespace P3\Exception;

/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class Base extends \Exception
{
	public function __construct($msg, $args = array(), $code = 0){
		parent::__construct(vsprintf($msg, $args), $code);
	}
}

?>
