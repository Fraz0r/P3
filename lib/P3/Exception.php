<?php

namespace P3;

class Exception extends \Exception
{
	public function __construct($msg, $args = array(), $code = 0){
		parent::__construct(vsprintf($msg, $args), $code);
	}
}

?>
