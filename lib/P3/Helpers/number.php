<?php
/**
 * Description of number
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */

abstract class number {
	public static function english_extension($number)
	{
		/* Ugh.. (It's to access it as an array) Shuck your forts! */
		$number = (string)$number;
		$last = (int)($number[(strlen($number) - 1)]);
		switch($last) {
			case 1:
				$extension = 'st';
				break;
			case 2:
				$extension = 'nd';
				break;
			case 3:
				$extension = 'rd';
				break;
			default:
				$extension = 'th';
			break;
		}

		return $number.$extension;
	}
}
?>
