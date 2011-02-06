<?php

/**
 * Description of File
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */

namespace P3\Session\Handler;

class File extends Base
{
	private $_savePath = null;

	function open($save_path, $session_name)
	{
		$this->_savePath = $save_path;
		return(true);
	}

	function close()
	{
		return(true);
	}

	function read($id)
	{
		$sess_file = $this->_savePath."/sess_$id";
		return (string) @file_get_contents($sess_file);
	}

	function write($id, $sess_data)
	{
		$sess_file = $this->_savePath."/sess_$id";
		if ($fp = @fopen($sess_file, "w")) {
			$return = fwrite($fp, $sess_data);
			fclose($fp);
			return $return;
		} else {
			return(false);
		}
	}

	function destroy($id)
	{
		$sess_file = $this->_savePath."/sess_$id";
		return(@unlink($sess_file));
	}

	function gc($maxlifetime)
	{
		foreach (glob("{$this->_savePath}/sess_*") as $filename) {
			if (filemtime($filename) + $maxlifetime < time()) {
				@unlink($filename);
			}
		}
		return true;
	}
}
?>