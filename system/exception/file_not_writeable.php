<?php

namespace P3\System\Exception;

/**
 * Description of file_not_readable
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class FileNotWriteable extends \P3\Exception\SystemException
{
	public function __construct($file)
	{
		parent::__construct('File \'%s\' is not writeable', array($file));
	}
}

?>