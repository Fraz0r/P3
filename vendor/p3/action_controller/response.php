<?php

namespace P3\ActionController;

/**
 * Description of response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Response extends \P3\Net\Http\Response
{
	private $_request;

	public function __construct(\P3\Net\Http\Request $request, $body, array $headers = array(), $code = self::STATUS_OK)
	{
		$this->_request = $request;

		parent::__construct($body, $headers, $code);
	}
}

?>
