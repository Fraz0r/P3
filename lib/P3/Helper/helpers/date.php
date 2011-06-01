<?php

/**
 * Date Helper
 * 
 * @package P3\ActiveRecord\Association
 * @version $Id$
 * 
 * @todo Structure month array like you did states in geo
 */
abstract class date extends P3\Helper\Base
{
	const DAYS_IN_YEAR = 365;

	private static $_monthDays = array(
		31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31
	);

	private static $_months = array(
		'Jan' => 'January',
		'Feb' => 'February',
		'Mar' => 'March',
		'Apr' => 'April',
		'May' => 'May',
		'Jun' => 'June',
		'Jul' => 'July',
		'Aug' => 'August',
		'Sep' => 'September',
		'Oct' => 'October',
		'Nov' => 'November',
		'Dec' => 'December'
	);

	private static $_timezoneTable = array(
		"Pacific/Midway" => "(GMT-11:00) Midway Island, Samoa",
		"America/Adak" => "(GMT-10:00) Hawaii-Aleutian",
		"Etc/GMT+10" => "(GMT-10:00) Hawaii",
		"Pacific/Marquesas" => "(GMT-09:30) Marquesas Islands",
		"Pacific/Gambier" => "(GMT-09:00) Gambier Islands",
		"America/Anchorage" => "(GMT-09:00) Alaska",
		"America/Ensenada" => "(GMT-08:00) Tijuana, Baja California",
		"Etc/GMT+8" => "(GMT-08:00) Pitcairn Islands",
		"America/Los_Angeles" => "(GMT-08:00) Pacific Time (US & Canada)",
		"America/Denver" => "(GMT-07:00) Mountain Time (US & Canada)",
		"America/Chihuahua" => "(GMT-07:00) Chihuahua, La Paz, Mazatlan",
		"America/Dawson_Creek" => "(GMT-07:00) Arizona",
		"America/Belize" => "(GMT-06:00) Saskatchewan, Central America",
		"America/Cancun" => "(GMT-06:00) Guadalajara, Mexico City, Monterrey",
		"Chile/EasterIsland" => "(GMT-06:00) Easter Island",
		"America/Chicago" => "(GMT-06:00) Central Time (US & Canada)",
		"America/New_York" => "(GMT-05:00) Eastern Time (US & Canada)",
		"America/Havana" => "(GMT-05:00) Cuba",
		"America/Bogota" => "(GMT-05:00) Bogota, Lima, Quito, Rio Branco",
		"America/Caracas" => "(GMT-04:30) Caracas",
		"America/Santiago" => "(GMT-04:00) Santiago",
		"America/La_Paz" => "(GMT-04:00) La Paz",
		"Atlantic/Stanley" => "(GMT-04:00) Faukland Islands",
		"America/Campo_Grande" => "(GMT-04:00) Brazil",
		"America/Goose_Bay" => "(GMT-04:00) Atlantic Time (Goose Bay)",
		"America/Glace_Bay" => "(GMT-04:00) Atlantic Time (Canada)",
		"America/St_Johns" => "(GMT-03:30) Newfoundland",
		"America/Araguaina" => "(GMT-03:00) UTC-3",
		"America/Montevideo" => "(GMT-03:00) Montevideo",
		"America/Miquelon" => "(GMT-03:00) Miquelon, St. Pierre",
		"America/Godthab" => "(GMT-03:00) Greenland",
		"America/Argentina/Buenos_Aires" => "(GMT-03:00) Buenos Aires",
		"America/Sao_Paulo" => "(GMT-03:00) Brasilia",
		"America/Noronha" => "(GMT-02:00) Mid-Atlantic",
		"Atlantic/Cape_Verde" => "(GMT-01:00) Cape Verde Is.",
		"Atlantic/Azores" => "(GMT-01:00) Azores",
		"Europe/Belfast" => "(GMT) Greenwich Mean Time : Belfast",
		"Europe/Dublin" => "(GMT) Greenwich Mean Time : Dublin",
		"Europe/Lisbon" => "(GMT) Greenwich Mean Time : Lisbon",
		"Europe/London" => "(GMT) Greenwich Mean Time : London",
		"Africa/Abidjan" => "(GMT) Monrovia, Reykjavik",
		"Europe/Amsterdam" => "(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna",
		"Europe/Belgrade" => "(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague",
		"Europe/Brussels" => "(GMT+01:00) Brussels, Copenhagen, Madrid, Paris",
		"Africa/Algiers" => "(GMT+01:00) West Central Africa",
		"Africa/Windhoek" => "(GMT+01:00) Windhoek",
		"Asia/Beirut" => "(GMT+02:00) Beirut",
		"Africa/Cairo" => "(GMT+02:00) Cairo",
		"Asia/Gaza" => "(GMT+02:00) Gaza",
		"Africa/Blantyre" => "(GMT+02:00) Harare, Pretoria",
		"Asia/Jerusalem" => "(GMT+02:00) Jerusalem",
		"Europe/Minsk" => "(GMT+02:00) Minsk",
		"Asia/Damascus" => "(GMT+02:00) Syria",
		"Europe/Moscow" => "(GMT+03:00) Moscow, St. Petersburg, Volgograd",
		"Africa/Addis_Ababa" => "(GMT+03:00) Nairobi",
		"Asia/Tehran" => "(GMT+03:30) Tehran",
		"Asia/Dubai" => "(GMT+04:00) Abu Dhabi, Muscat",
		"Asia/Yerevan" => "(GMT+04:00) Yerevan",
		"Asia/Kabul" => "(GMT+04:30) Kabul",
		"Asia/Yekaterinburg" => "(GMT+05:00) Ekaterinburg",
		"Asia/Tashkent" => "(GMT+05:00) Tashkent",
		"Asia/Kolkata" => "(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi",
		"Asia/Katmandu" => "(GMT+05:45) Kathmandu",
		"Asia/Dhaka" => "(GMT+06:00) Astana, Dhaka",
		"Asia/Novosibirsk" => "(GMT+06:00) Novosibirsk",
		"Asia/Rangoon" => "(GMT+06:30) Yangon (Rangoon)",
		"Asia/Bangkok" => "(GMT+07:00) Bangkok, Hanoi, Jakarta",
		"Asia/Krasnoyarsk" => "(GMT+07:00) Krasnoyarsk",
		"Asia/Hong_Kong" => "(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi",
		"Asia/Irkutsk" => "(GMT+08:00) Irkutsk, Ulaan Bataar",
		"Australia/Perth" => "(GMT+08:00) Perth",
		"Australia/Eucla" => "(GMT+08:45) Eucla",
		"Asia/Tokyo" => "(GMT+09:00) Osaka, Sapporo, Tokyo",
		"Asia/Seoul" => "(GMT+09:00) Seoul",
		"Asia/Yakutsk" => "(GMT+09:00) Yakutsk",
		"Australia/Adelaide" => "(GMT+09:30) Adelaide",
		"Australia/Darwin" => "(GMT+09:30) Darwin",
		"Australia/Brisbane" => "(GMT+10:00) Brisbane",
		"Australia/Hobart" => "(GMT+10:00) Hobart",
		"Asia/Vladivostok" => "(GMT+10:00) Vladivostok",
		"Australia/Lord_Howe" => "(GMT+10:30) Lord Howe Island",
		"Etc/GMT-11" => "(GMT+11:00) Solomon Is., New Caledonia",
		"Asia/Magadan" => "(GMT+11:00) Magadan",
		"Pacific/Norfolk" => "(GMT+11:30) Norfolk Island",
		"Asia/Anadyr" => "(GMT+12:00) Anadyr, Kamchatka",
		"Pacific/Auckland" => "(GMT+12:00) Auckland, Wellington",
		"Etc/GMT-12" => "(GMT+12:00) Fiji, Kamchatka, Marshall Is.",
		"Pacific/Chatham" => "(GMT+12:45) Chatham Islands",
		"Pacific/Tongatapu" => "(GMT+13:00) Nuku'alofa",
		"Pacific/Kiritimati" => "(GMT+14:00) Kiritimati"
	);

	public static function daysForSelect(array $options = array())
	{
		$range = \number::range(1, 31);

		return array_combine($range, $range);
	}

	public static function monthsForSelect(array $options = array())
	{
		$months = (isset($options['use_short_months']) && $options['use_short_months']) ? array_keys(self::$_months) : array_values(self::$_months);
		return array_combine(number::range(1, 12), $months);
	}

	public static function timezoneForSelect(array $options = array())
	{
		return self::$_timezoneTable;
	}

	public static function yearsForSelect(array $options = array())
	{
		if(!isset($options['start_year']) || !isset($options['end_year']))
			$current_year = (int)date("Y");

		$start = isset($options['start_year']) ? (int)$options['start_year'] : $current_year - 40;
		$end   = isset($options['end_year']) ? (int)$options['end_year'] : $current_year;
		$range = \number::range($start, $end);

		return array_combine($range, $range);
	}

}

?>
