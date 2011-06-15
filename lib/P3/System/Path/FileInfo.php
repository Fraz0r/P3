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
	/**
	 * Return file extension (This wasn't added to the SPL Library until PHP 5.3.6)
	 * If the method is there, it's used.  Otherwise we handle it on our own
	 * 
	 * @return string extension
	 */
	public function getExtension()
	{
		if (method_exists(get_parent_class($this), 'getExtension')) {
			return(parent::getExtension());
		} else {
			return(substr($this->getFilename(), strrpos($this->getFilename(), '.')+1));
		}
	}

	/**
	 * Convenience method for getting the name of the file, minus the extension
	 * 
	 * @return string
	 */
	public function getFilenameNoExtension()
	{
		return $this->getBasename('.'.$this->getExtension());
	}
}