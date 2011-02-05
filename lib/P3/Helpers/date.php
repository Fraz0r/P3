<?php

abstract class date extends P3\Helper
{
	private static $_months = array(
		'January',
		'February',
		'March',
		'April',
		'May',
		'June',
		'July',
		'August',
		'September',
		'October',
		'November',
		'December'
	);

	private static $_monthsAbr = array(
		'jan',
		'feb',
		'mar',
		'apr',
		'may',
		'jun',
		'jul',
		'aug',
		'sept',
		'oct',
		'nov',
		'dec'
	);

	public static function monthsForSelect()
	{
		return array_combine(number::range(1, 12), self::$_months);
	}
}

?>