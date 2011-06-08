<?php

namespace P3\System\Path;

/**
 * This class extends SplFileInfo, handling inconsistencies between PHP versions
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\System\Path
 * @version $Id$
 */
class FileInfo extends \SplFileInfo
{
	public function getExtension()
	{
		// SPL only added getExtension in 5.3.6 ... so do a check
		if (method_exists(get_parent_class($this), 'getExtension')) {
			return(parent::getExtension());
		} else {
			return(substr($this->getFilename(), strrpos($this->getFilename(), '.')+1));
		}
	}
}