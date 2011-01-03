<?php
/**
 * Description of Request
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class P3_Request
{
	public function isXHR()
	{
		return(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
}
?>
