<?php
/**
 * HTML Helpers
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
abstract class html extends P3\Helper\Base
{
	public static function _($tag_name, array $attrs = [], $oneline = false)
	{
		$ret = '<'.$tag_name;

		$attributes = [];

		foreach($attrs as $k => $v)
			$attributes[] = "{$k}=\"{$v}\"";

		if(count($attributes))
			$ret .= ' '.implode(' ', $attributes);

		if($oneline)
			$ret .= ' />';
		else
			$ret .= '>';
		
		return $ret;
	}

	public static function _c($tag_name)
	{
		return '</'.$tag_name.'>';
	}
}

?>
