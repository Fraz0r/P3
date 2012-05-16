<?php

/**
 * Geo helpers
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
class geo extends \P3\Helper\Base
{
	const STATE_FORMAT_FULL = 1;
	const STATE_FORMAT_ABR  = 2;

	public static $STATE_LIST = array('AL'=>"Alabama", 'AK'=>"Alaska",  'AZ'=>"Arizona",  'AR'=>"Arkansas",  'CA'=>"California",  'CO'=>"Colorado",  'CT'=>"Connecticut",  'DE'=>"Delaware",  'DC'=>"District Of Columbia",  'FL'=>"Florida",  'GA'=>"Georgia",  'HI'=>"Hawaii",  'ID'=>"Idaho",  'IL'=>"Illinois",  'IN'=>"Indiana",  'IA'=>"Iowa",  'KS'=>"Kansas",  'KY'=>"Kentucky",  'LA'=>"Louisiana",  'ME'=>"Maine",  'MD'=>"Maryland",  'MA'=>"Massachusetts",  'MI'=>"Michigan",  'MN'=>"Minnesota",  'MS'=>"Mississippi",  'MO'=>"Missouri",  'MT'=>"Montana", 'NE'=>"Nebraska", 'NV'=>"Nevada", 'NH'=>"New Hampshire", 'NJ'=>"New Jersey", 'NM'=>"New Mexico", 'NY'=>"New York", 'NC'=>"North Carolina", 'ND'=>"North Dakota", 'OH'=>"Ohio",  'OK'=>"Oklahoma",  'OR'=>"Oregon",  'PA'=>"Pennsylvania",  'RI'=>"Rhode Island",  'SC'=>"South Carolina",  'SD'=>"South Dakota", 'TN'=>"Tennessee",  'TX'=>"Texas",  'UT'=>"Utah",  'VT'=>"Vermont",  'VA'=>"Virginia",  'WA'=>"Washington",  'WV'=>"West Virginia",  'WI'=>"Wisconsin",  'WY'=>"Wyoming");

	/**
	 * Returns an array of states.  Either full, or abreviated
	 *
	 * @param int $format Format of states to return
	 * @return array Array of states
	 */
	public static function states($format = null)
	{
		$format = is_null($format) ? self::STATE_FORMAT_FULL : $format;

		switch($format) {
			case self::STATE_FORMAT_ABR:
				return array_keys(self::$STATE_LIST);
				break;
			case self::STATE_FORMAT_FULL:
				return array_values(self::$STATE_LIST);
				break;
			default:
				throw new \P3\Exception\HelperException('Unkown State Format used in getStates');
		}
	}
}
?>