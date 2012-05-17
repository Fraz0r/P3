<?php

namespace P3\ActionController;

/**
 * Description of response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Response extends \P3\Net\Http\Response
{
	private $_conroller;

	public function __construct(\P3\ActionController\Base $conroller, $body, array $headers = array(), $code = self::STATUS_OK)
	{
		$this->_conroller = $conroller;

		parent::__construct($body, $headers, $code);
	}
}

?>
