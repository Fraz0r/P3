<?php

namespace P3\System\Exception;

/**
 * Description of file_class_mismatch
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class FileClassMismatch extends \P3\Exception\SystemException
{
	public function __construct($file, $expected_class_name)
	{
		parent::__construct('Expected [%s] to instantiate \'%s\'', array($file, $expected_class_name), 500);
	}
}

?>