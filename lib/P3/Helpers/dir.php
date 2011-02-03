<?php

/**
 * Directory Helper
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class dir extends P3_Helper
{
	const OPT_DESTROY_FILES_ON_DELETE = 1;
	const OPT_RETURN_FULL_PATHS = 2;

	private $_dir = null;

//Public
	public function  __construct($dir, array $options = array())
	{
		$this->_dir = $dir;
	}

	public function files()
	{
		return self::getFileList($this->_dir, $this->_opts);
	}

	public function destroy()
	{
		return self::rm($this->_dir, $this->_opts);
	}

//Protected

//Static
	public static function getFileList($dir, array $options = array())
	{
		if(!is_dir($dir)) return false;

		try {
			$objects = scandir($dir);
		} catch(Exception $e) {
			return false;
		}

		foreach($objects as &$o) {
			if($o == '.' || $o == '..') unset($o);
			else {
				if(isset($options[self::OPT_RETURN_FULL_PATHS]) && $options[self::OPT_RETURN_FULL_PATHS]) $o = rtrim($dir, '/').'/'.$o;
			}
		}

		return $objects;
	}

	public static function rm($dir, array $options = array())
	{
		if(isset($options[self::OPT_DESTROY_FILES_ON_DELETE]) && $options[self::OPT_DESTROY_FILES_ON_DELETE]) {
			$files = self::getFileList($dir, array(self::OPT_RETURN_FULL_PATHS => true));
			foreach($files as $file) unlink($file);
		}
		return rmdir($dir);
	}
}
?>