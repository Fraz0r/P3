<?php

/**
 * Description of arr
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class arr
{
	public static function extract($array, $desired_keys)
	{
		return array_intersect_key($array, array_flip($desired_keys));
	}

	public static function filter($array, $filter_keys)
	{
		return array_diff_key($array, array_flip($filter_keys));
	}
}

?>