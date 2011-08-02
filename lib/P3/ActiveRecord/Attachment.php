<?php

namespace P3\ActiveRecord;
use       P3\System\Path\FileInfo;

/**
 * This is the class that is returned when interfacing with model attachments.
 * 
 * A word of warning - The attachment path is indeed overrideable.  However,
 * if you do so - MAKE SURE your model's images are seperated uniquely by parent type, id
 * and attachement name.  This class will delete EVERYTHING in a folder for what 
 * it beleives does not belong.  If you need clarification on this, look through the 
 * delete() method's source (Definitely do this if you are overriding the path, give yourself
 * the peace of mind)
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord
 * @version $Id$
 */
class Attachment extends \P3\Model\Base
{
	/**
	 * ImageMagick Geometries
	 * 
	 * @see http://www.imagemagick.org/script/command-line-processing.php#geometry
	 */
	const GEOMETRY_SCALE_NONE     = 0;
	const GEOMETRY_SCALE_GLOBAL   = 1; // scale%
	const GEOMETRY_SCALE_BOTH     = 2; // scale-x%xscale-y%
	const GEOMETRY_SCALE_WIDTH    = 3; // width
	const GEOMETRY_SCALE_HEIGHT   = 4; // xheight
	const GEOMETRY_SCALE_MAX      = 5; // widthxheight
	const GEOMETRY_SCALE_MIN      = 6; // widthxheight^
	const GEOMETRY_SCALE_EMPHATIC = 7; // widthxheight!
	const GEOMETRY_SCALE_IF_GREATER      = 8; // widthxheight>
	const GEOMETRY_SCALE_IF_BOTH_GREATER = 9; // widthxheight<
	const GEOMETRY_SCALE_AREA            = 10; // @area

	const GEOMETRY_CROP                  = 11; // widthxheight#

	/**
	 * Array, mapping styles to parseable regular expressions
	 * 
	 * @var array
	 */
	private static $_GEOMETRY_INTERPRETATION_MAPPING = array(
		self::GEOMETRY_SCALE_NONE     => null,
		self::GEOMETRY_SCALE_GLOBAL   => '^[\d]*%$',
		self::GEOMETRY_SCALE_BOTH     => '^[\d]*%?x[\d]*%$',
		self::GEOMETRY_SCALE_WIDTH    => '^[\d]*$',
		self::GEOMETRY_SCALE_HEIGHT   => '^x[\d]*$',
		self::GEOMETRY_SCALE_MAX      => '^[\d]*x[\d]*$',
		self::GEOMETRY_SCALE_MIN      => '^[\d]*x[\d]*\^$',
		self::GEOMETRY_SCALE_EMPHATIC => '^[\d]*x[\d]*!$',
		self::GEOMETRY_SCALE_IF_GREATER      => '^[\d]*x[\d]*>$',
		self::GEOMETRY_SCALE_IF_BOTH_GREATER => '^[\d]*x[\d]*<$',
		self::GEOMETRY_SCALE_AREA            => '^@[\d]*$',
		self::GEOMETRY_CROP                  => '^[\d]*x[\d]*#$'
	);

	/**
	 * Field name for this attachment
	 * 
	 * @var string
	 */
	private $_name    = null;

	/**
	 * The parent model owning this attachment
	 * 
	 * @var P3\ActiveRecord\Base
	 */
	private $_parent = null;

	/**
	 * Options - overridebale if need be.  See wiki on attachments.
	 * 
	 * @var array
	 */
	private $_options = array(
		'path' => ':p3_root/htdocs/:class/:attachment/:id/:style_:basename.:extension',
		'url'  => '/:class/:attachment/:id/:style_:basename.:extension',
		'whiney_thumbnails' => true
	);


//- Public
	/**
	 * Instantiate Attachment
	 * 
	 * @param P3\ActiveRecord\Base $model Parent model, owning the attachment
	 * @param string $attachment Field name for the attachment
	 * @param array $options Options from record
	 */
	public function __construct($model, $attachment, array $options = array())
	{
		$this->_parent  = $model;
		$this->_name    = $attachment;

		foreach($options as $k => $v)
			$this->_options[$k] = $v;
	}

	/**
	 * Deletes all files for this attachment, updates the parent record, and then
	 * deletes physical folder
	 * 
	 * @return boolean
	 */
	public function delete($delete_dir_if_empty = true)
	{
		$this->_parent->{$this->_name.'_file_name'}    = '';
		$this->_parent->{$this->_name.'_file_size'}    = 0;
		$this->_parent->{$this->_name.'_content_type'} = '';
		$this->_parent->save(array('validate' => false, 'save_attachments' => false));


		/* Since the parent is updated by this point, this attachment now thinks everything is "junk" */
		$this->deleteJunk();

		/* Now, lets remove the parent folder to keeps things clean */
		$dir = dirname($this->path());
		if(is_dir($dir))
			rmdir(dirname($this->path()));

		return true;
	}

	/**
	 * Deletes anything in the attachment folder which does not belong
	 * 
	 * @return booelan true, always
	 */
	public function deleteJunk()
	{
		$path = $this->path();
		$dir  = dirname($path);

		/* If the attachment already doesn't exist, we're "clean" */
		if(!is_dir($dir))
			return true;

		$valid   = array(basename($path));

		if(isset($this->_options['styles']))
			foreach($this->_options['styles'] as $k => $v)
				$valid[] = basename($this->path($k));

		$valid = array_merge($valid, array('.', '..'));

		$objects = scandir(dirname($this->path()));

		foreach($objects as $object)
			if(!in_array($object, $valid))
				unlink("{$dir}/{$object}");					

		return true;
	}

	/**
	 * Determines if this attachment exists.  We use readable, bc if the file
	 * exists and isn't readable - it does us no good anyway
	 * 
	 * @return boolean whether or not the file exists (readable)
	 */
	public function exists()
	{
		return $this->_file_info(true)->isReadable();
	}

	/**
	 * Returns filename of attachment
	 * 
	 * @return string filename
	 */
	public function filename()
	{
		return $this->_filename();
	}

	/**
	 * Returns filesize of attachment
	 * 
	 * @return int filesize 
	 */
	public function filesize()
	{
		return (int)$this->_parent->{$this->_name.'_file_size'};
	}

	/**
	 * Returns the full path for this attachment.  The style option is optional.
	 * If it's passed, the path for the style will be returned, otherwise the path
	 * of the base image will be returned.  Note, the style option is for images
	 * ONLY.
	 * 
	 * @param string $style [Optional] style to retrieve
	 * @return string path to attachment 
	 */
	public function path($style = null)
	{
		$template = $this->_options['path'];
		return $this->_parseTemplateString($template, $style);
	}

	/**
	 * Reprocess attachment styles
	 * 
	 * @return boolean whether or not the reprocess was successful
	 */
	public function reprocess()
	{
		return $this->exists() ? $this->_generateStyles() : false;
	}

	/**
	 * Saves attachment based on data from _FILES super global (passed from ActiveRecord).
	 * Will generate styled images upon successful upload
	 * 
	 * @param array $file_data data from _FILES super global (for this field, not entire array)
	 * 
	 * @return boolean successfullness
	 */
	public function save($file_data)
	{
		$data = $file_data;
		$field = $this->_name;
		$flag = true;

		switch($data['error'][$field])
		{
			case \UPLOAD_ERR_OK:
				break;
			case \UPLOAD_ERR_NO_FILE:
				return true;
			default:
				$ret = false;
				$this->_parent->_addError($field, 'Upload Error ['.$data['error'][$field].']');
				return false;
		}


		$this->_parent->{$field.'_file_name'} = $data['name'][$field];
		$path = $this->path();

		if(!is_dir(dirname($path)) && !@mkdir(dirname($path), 0777, true))
			throw new \P3\Exception\ActiveRecordException("Attachment directory doesn't exist (%s: %s)", array(get_class($this->_parent), dirname($path)), 500);


		if(!move_uploaded_file($data['tmp_name'][$field], $path)) {
			$ret = false;
			$this->_parent->_addError($field, 'Upload failed');
			return false;
		}

		$finfo = finfo_open(\FILEINFO_MIME_TYPE);

		if(!$finfo)
			throw new \P3\Exception\ActiveRecordException("Failed to stat file.  See finfo_open on php.net for info.", array(), 500);

		$this->_parent->{$field.'_content_type'} = finfo_file($finfo, $path);
		finfo_close($finfo);

		$this->_parent->{$field.'_file_size'} = $data['size'][$field];

		if(isset($this->_options['styles']))
			$flag = $flag && $this->_generateStyles();

		$this->deleteJunk();

		$flag = $flag && $this->_parent->save(array('save_attachments' => false));

		return $flag;
	}

	/**
	 * Returns the public url for the [optional] style passed.  If no style is passed,
	 * the base image url is returned.
	 * 
	 * @param string $style style name
	 * @return string url for image style
	 */
	public function url($style = null)
	{
		if($this->exists()) {
			$template = $this->_options['url'];
			return $this->_parseTemplateString($template, $style);
		} else {
			if(isset($this->_options['default_url'])) {
				if(is_array($this->_options['default_url'])) {
					if(is_null($style))
						return !isset($this->_options['default_url']['default']) ? false : $this->_options['default_url']['default'];
					else 
						return !isset($this->_options['default_url'][$style]) ? false : $this->_options['default_url'][$style];
				} else {
					return $this->_options['default_url'];
				}
			} else {
				return false;
			}
		}
	}

//- Private
	/**
	 * Returns a FileInfo object (extended from SPL's) for the base image.
	 * If use_full_path is true, the full path is passed to the contructor.  Otherwise,
	 * just the basename of the file is passed.
	 * 
	 * @param boolean $use_full_path whether or not to use the full path for the image in the constructor
	 * @return P3\System\Path\FileInfo  file info object
	 */
	private function _file_info($use_full_path = false)
	{
		$path = $use_full_path ? $this->path() : $this->_filename();

		return new FileInfo($path);
	}

	/**
	 * Uses the parent model to determine the basename of the of the BASE image
	 * 
	 * @return string file name
	 */
	private function _filename()
	{
		$ret = $this->_parent->{$this->_name.'_file_name'};

		return empty($ret) ? false : $ret;
	}

	/**
	 * This is called during the save() process.  Just loops through styles assigned 
	 * to the image, and generates the images appropriately
	 * 
	 * @return boolean successfullness 
	 */
	private function _generateStyles()
	{
		$flag = true;

		foreach($this->_options['styles'] as $style_name => $size)
			$flag = $flag && $this->_getStyleFor($size)->resize($this->path(), $this->path($style_name), $size);

		return $flag;
	}

	/**
	 * Interprets string passed, and returns the an Attachment Style with the proper
	 * flag set.
	 * 
	 * @param string $style_str style string to parse
	 * @param array $options options to pass to Style
	 * 
	 * @return P3\ActiveRecord\Attachment\Style attachment style for processing
	 */
	private function _getStyleFor($style_str = null, array $options = array())
	{
		return new Attachment\Style($this->_interpretStyle($style_str), $options);
	}

	/**
	 * Interprets style string passed, and returns appropriate scaling method.  
	 * If nothing is matched, GEOMETRY_SCALE_NONE is returned - which is the same
	 * functionality used on the Base image (unless a default style was defined).
	 * 
	 * @param string $style_str style string to parse
	 * 
	 * @return int matched style 
	 */
	private function _interpretStyle($style_str = null)
	{
		if(is_null($style_str))
			return self::GEOMETRY_SCALE_NONE;

		$match = false;
		foreach(self::$_GEOMETRY_INTERPRETATION_MAPPING as $style => $pattern) {
			if(!is_null($pattern)) {
				$pattern = '~'.$pattern.'~';

				if(preg_match($pattern, $style_str)) {
					$match = $style;
					break;
				}
			}
		}

		return !$match ? self::GEOMETRY_SCALE_NONE : $match;
	}

	/**
	 * Parses and returns passed template $str based on $style
	 * 
	 * @param string $str style string to parse
	 * @param int $style style being parsed
	 * 
	 * @return string parsed string from template
	 */
	private function _parseTemplateString($str, $style = null)
	{
		if(is_null($style))
			$str = str_replace(':style_', '', $str);

		$info = $this->_file_info();

		$replace = array(
			':p3_root'    => \P3\ROOT,
			':class'      => \str::fromCamelCase(get_class($this->_parent)),
			':attachment' => $this->_name,
			':id'         => $this->_parent->id(),
			':style'      => $style,
			':basename'   => $info->getFilenameNoExtension(),
			':extension'  => $info->getExtension()
		);

		return str_replace(array_keys($replace), array_values($replace), $str);
	}
}

?>