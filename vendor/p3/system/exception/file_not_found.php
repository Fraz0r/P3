<?php

namespace P3\System\Exception;

require_once(\P3\PATH.'/exception/system_exception.php');

/**
 * Description of file_not_found
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class FileNotFound extends \P3\Exception\SystemException
{
	public function __construct($path)
	{
		return parent::__construct('%s', array($path));
	}
}

?>
