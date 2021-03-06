<?php
/**
 * Numeric Helpers
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\Helper
 * @version $Id$
 */
abstract class number extends P3\Helper\Base
{
	/**
	 * Returns an array containing the desired range
	 *
	 * @param int $start
	 * @param int $end
	 * @return array
	 */
	public static function range($start, $end, $combine = false)
	{
		$ret = range($start, $end);

		return $combine ? array_combine($ret, $ret) : $ret;
	}

	/**
	 * Number to format to money
	 *
	 * @param float $number Number to format
	 * @param integer $decimals Number of decimal places to include
	 * @return string Formatted number string
	 */
	public static function to_money($number)
	{
		return money_format('%n', $number);
	}

	/**
	 * Formats integer with english extension (e.g. 1 -&gt; 1st)
	 *
	 * @param int $number Number to format
	 * @return string Formatted number string
	 */
	public static function with_english_extenstion($number)
	{
		/* Ugh.. (It's to access it as an array) Shuck your forts! */
		$number = (string)$number;
		$last   = (int)($number[(strlen($number) - 1)]);

		/* Needed to handle 11's */
		if(strlen($number) >= 2)
			$slast = (int)($number[(strlen($number) - 2)]);
	 	else
			$slast = null;

		switch($last) {
			case 1:
				if($slast == 1) continue;
				$extension = 'st';
				break;
			case 2:
				if($slast == 1) continue;
				$extension = 'nd';
				break;
			case 3:
				if($slast == 1) continue;
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
