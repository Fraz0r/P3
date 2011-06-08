<?php

namespace P3\ActiveRecord;
use       P3\System\Path\FileInfo;

/**
 * This is the class that is returned when interfacing with model attachments
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord
 * @version $Id$
 */
class Attachment extends \P3\Model\Base
{
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
		self::GEOMETRY_CROP                  => '^[\d]*#$'
	);

	private $_name    = null;
	private $_parent = null;

	private $_options = array(
		'path' => ':p3_root/htdocs/:class/:attachment/:id/:style_:basename.:extension',
		'url'  => '/:class/:attachment/:id/:style_:basename.:extension',
		'whiney_thumbnails' => true
	);


//- Public
	public function __construct($model, $attachment, array $options = array())
	{
		$this->_parent  = $model;
		$this->_name    = $attachment;

		foreach($options as $k => $v)
			$this->_options[$k] = $v;
	}

	public function exists()
	{
		return $this->_file_info()->isReadable();
	}

	public function save()
	{
		if($this->exists() && isset($this->_options['styles']))
			$this->_generateStyles();
	}

	public function url($style = null)
	{
		$template = $this->_options['url'];
		return $this->_parseTemplateString($template, $style);
	}

	public function path($style = null)
	{
		$template = $this->_options['path'];
		return $this->_parseTemplateString($template, $style);
	}

//- Private
	private function _file_info()
	{
		return new FileInfo($this->path());
	}

	private function _filename()
	{
		return $this->_parent->{$this->_name.'_file_name'};
	}

	private function _generateStyles()
	{
		foreach($this->_options['styles'] as $style_name => $size) {
			$style = $this->_getStyleFor($size);
			$style->resize($this->path(), $this->path($style_name));
		}
	}

	private function _getStyleFor($style_str = null, array $options = array())
	{
		return new Attachment\Style($this->_interpretStyle($style_str), $options);
	}

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
			':basename'   => $info->getBasename(),
			':extension'  => $info->getExtension()
		);
		
		return str_replace(array_keys($replace), array_values($replace), $str);
	}
}

?>