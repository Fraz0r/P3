<?php

/**
 * Description of time
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class time extends \P3\Helper\Base
{
	public function distance_in_words($from_time, $to_time, $include_seconds = false, array $options = array())
	{
		$from_time = is_string($from_time) ? new \DateTime($from_time) : $from_time;
		$to_time = is_string($to_time) ? new \DateTime($to_time) : $to_time;

		$interval = $from_time->diff($to_time); 
		var_dump($interval);

		if($include_seconds) {
		} else {
			if($interval->y < 1) {
			}
		}
	}
}

?>