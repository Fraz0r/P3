<?php

/**
 * Description of Route
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\Routing;

class Route {
	protected $_method  = 'get';
	protected $_options = array();

	public function __construct($path, $options)
	{
		if(isset($options['method'])) $this->_method = $options['method'];
	}
}
?>