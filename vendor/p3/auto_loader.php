<?php

namespace P3;
use str;

require_once(PATH.'/helper/helpers/str.php');
/**
 * Description of AutoLoader
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class AutoLoader 
{
	public static function load_missing($class_name)
	{
		$path = self::_path_for_class($class_name);

		if(!(@include($path))) {
			require_once(PATH.'/system/exception/file_not_found.php');
			throw new System\Exception\FileNotFound($path);
		}
	}

//- Private
	private static function _path_for_class($class_name)
	{
		if(!strpos($class_name, '\\'))
			return str::from_camel($class_name).'.php';

		return implode(DIRECTORY_SEPARATOR, array_map(function($v){ 
			return str::from_camel($v);
		}, explode('\\', $class_name))).'.php';
	}
}

?>