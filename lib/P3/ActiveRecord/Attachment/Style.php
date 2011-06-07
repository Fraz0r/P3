<?php

namespace P3\ActiveRecord\Attachment;
use       P3\ActiveRecord\Attachment;

/**
 * Description of Style
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Attachment
 * @version $Id$
 */
class Style 
{
	private $_options = array(
		'path' => ':p3_root/htdocs/:class/:attachment/:id/:style_:basename.:extension',
		'url'  => '/:class/:attachment/:id/:style_:basename.:extension',
		'whiney_thumbnails' => true
	);

	private $_scale_type = null;

	public function __construct($scale_type, array $options = array())
	{
		$this->_scale_type = $scale_type;

		foreach($options as $k => $v)
			$this->_options[$k] = $v;
	}

	public function scale($save_path)
	{
		$flag = false;

		switch($this->_scale_type) {
			case Attachment::GEOMETRY_SCALE_NONE:
				break;
			case Attachment::GEOMETRY_SCALE_GLOBAL:
				break;
			case Attachment::GEOMETRY_SCALE_BOTH:
				break;
			case Attachment::GEOMETRY_SCALE_WIDTH:
				break;
			case Attachment::GEOMETRY_SCALE_HEIGHT:
				break;
			case Attachment::GEOMETRY_SCALE_MAX:
				break;
			case Attachment::GEOMETRY_SCALE_MIN:
				break;
			case Attachment::GEOMETRY_SCALE_EMPHATIC:
				break;
			case Attachment::GEOMETRY_SCALE_IF_GREATER:
				break;
			case Attachment::GEOMETRY_SCALE_IF_BOTH_GREATER:
				break;
			case Attachment::GEOMETRY_SCALE_AREA:
				break;
			default:
				throw new \P3\Exception\ActiveRecordException("Unknown scale handler set in attachment style");
		}

		return $flag;
	}

	public function _parseTemplateString($str)
	{
		$replace = array(
			':p3_root' => \P3\ROOT,
			':class'   => 'CLASS_NAME',
			':attachment' => 'ATTACHMENT_NAME',
			':id'         => 'MODEL_ID',
			':style'      => 'STYLENAME',
			':basename'   => 'BASENAME',
			':extension' => 'EXT'
		);
		
		return str_replace(array_keys($replace), array_values($replace), $str);
	}
}

?>