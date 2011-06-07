<?php

namespace P3\ActiveRecord;

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
		self::GEOMETRY_SCALE_AREA            => '^@[\d]*$'
	);

	private $_name    = null;
	private $_mode    = MODE_LOAD;
	private $_options = array();
	private $_parent = null;

//- Public
	public function __construct($model, $name, array $options = array())
	{
		$this->_name = $name;
		$this->_options = $options;
		$this->_parent  = $model;
	}

	public function save()
	{
	}

	public function url($style = null)
	{
	}

//- Private
	private function _generateStyles()
	{
		foreach($this->_options['styles'] as $style => $size) {
		}
	}

	public function _getStyleFor($style_str, array $options = array())
	{
		return new Attachment\Style($this->_interpretStyle($style_str), $options);
	}

	private function _interpretStyle($style_str)
	{
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
}

?>