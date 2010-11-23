<?php
/**
 * Description of Utils
 *
 * @author Tim Frazier <tim@essential-elements.net>
 */

class P3_CSV_Utils
{
	/**
	 * Prints an array to a CSV Line
	 * @param array $arr array to be printed
	 * @param string $delimiter delimeter placed between values
	 * @param bool $print echos if true, returns line if false
	 */
	public static function arrayToRow ( array $arr, $delimiter = ',', $print = true )
	{
		$line = implode($delimiter, $arr)."\n";
		if($print)
			echo $line;
		else
			return $line;
	}
}
?>
