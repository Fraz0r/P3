<?php

namespace P3\ActiveRecord\Attachment;
use       P3\ActiveRecord\Attachment;

if(!extension_loaded('imagick'))
	throw new \P3\Exception\IncompatableSystemException('Active Record Attachments require the ImageMagick PHP Extension to process/manipulate sizes');

/**
 * Description of Style
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Attachment
 * @version $Id$
 */
class Style 
{
	private $_scale_type = null;

	public function __construct($scale_type, array $options = array())
	{
		$this->_scale_type = $scale_type;

		foreach($options as $k => $v)
			$this->_options[$k] = $v;
	}

	public function resize($input, $output)
	{
		$flag = false;

		$image = new \Imagick($input);
		var_dump($image);

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
				var_dump($input);
				var_dump($output);
				break;
			case Attachment::GEOMETRY_SCALE_IF_BOTH_GREATER:
				break;
			case Attachment::GEOMETRY_SCALE_AREA:
				break;
			case Attachment::GEOMETRY_CROP:
				break;
			default:
				throw new \P3\Exception\ActiveRecordException("Unknown scale handler set in attachment style");
		}

		return $flag;
	}
}

?>