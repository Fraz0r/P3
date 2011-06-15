<?php

namespace P3\ActiveRecord\Attachment;
use       P3\ActiveRecord\Attachment;

/**
 * This class requires imagick.
 */
if(!extension_loaded('imagick'))
	throw new \P3\Exception\IncompatableSystemException('Active Record Attachments require the ImageMagick PHP PECL Extension to process/manipulate sizes');

/**
 * This class is used internally only.  It is used soley for resizing model attachments
 * based on the styles option passed.
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\ActiveRecord\Attachment
 * @version $Id$
 */
class Style 
{
	/**
	 * Scale type
	 * 
	 * @var int
	 */
	private $_scale_type = null;

//- Public
	/**
	 * Instantiate Style
	 * 
	 * @param int $scale_type scaling type
	 * @param array $options options 
	 */
	public function __construct($scale_type, array $options = array())
	{
		$this->_scale_type = $scale_type;

		foreach($options as $k => $v)
			$this->_options[$k] = $v;
	}

	/**
	 * Copies $input to $output.  Also resizes/crops based on current style.
	 * 
	 * @param string $input input path
	 * @param string $output output path
	 * @param string $size size string passed
	 * 
	 * @return boolean success 
	 * 
	 * @todo make gravity an option when cropping, instead of defaulting to center
	 */
	public function resize($input, $output, $size)
	{
		copy($input, $output);

		$image = new \Imagick($output);

		$to_size = $this->_getSizeOptions($image, $size);

		if(is_array($to_size) && $to_size['opt']['crop']) {
			$image->setImageGravity(\Imagick::GRAVITY_CENTER);
			$flag = $image->cropThumbnailImage($to_size['width'], $to_size['height']);
		} else {
			if(!$to_size)
				$flag = true;
			else
				$flag = $image->scaleImage($to_size['width'], $to_size['height'], $to_size['opt']['best_fit']);
		}

		if($flag)
			$flag = $image->writeImage($output);

		return $flag;
	}

//- Private
	/**
	 * This is used by resize().  It calls _parseSizeString to get the width
	 * and height gemoetries needed by resize().  It also contains the algorythms 
	 * to handle all the ImageMagick geometries within this class which it uses to
	 * know if we should crop or scale - and whether or not we should keep the proportions 
	 * of the image or not.
	 * 
	 * @see resize
	 * @see _parseSizeString
	 * 
	 * @param Imagick $image image being processed
	 * @param string $size_str string that was assigned to the style in the attachment's options
	 * 
	 * @return array parsed to_size array
	 */
	private function _getSizeOptions($image, $size_str)
	{
		$crop    = false;
		$best_fit = false;

		$to_size = $this->_parseSizeString($size_str);

		$geo = $image->getImageGeometry();
		$width  = $geo['width'];
		$height = $geo['height'];

		$landscape = $width >= $height;

		switch($this->_scale_type) {
			case Attachment::GEOMETRY_SCALE_NONE:
				$to_size = false;
				break;
			case Attachment::GEOMETRY_SCALE_GLOBAL:
				$to_size['height'] = $height * $to_size['height'] / 100;
				$to_size['width']  = $width * $to_size['width'] / 100;
				$bestfit = false;
				break;
			case Attachment::GEOMETRY_SCALE_BOTH:
				$to_size['height'] = $height * $to_size['height'] / 100;
				$to_size['width']  = $width * $to_size['width'] / 100;
				$bestfit = false;
				break;
			case Attachment::GEOMETRY_SCALE_WIDTH:
				$bestfit = false;
				$to_size['height'] = 0;
				break;
			case Attachment::GEOMETRY_SCALE_HEIGHT:
				$bestfit = false;
				$to_size['width'] = 0;
				break;
			case Attachment::GEOMETRY_SCALE_MAX:
				$best_fit = true;
				break;
			case Attachment::GEOMETRY_SCALE_MIN:
				if($landscape) {
					if($height < $to_size['height'])
						$to_size['width'] = 0;
				} else {
					if($width < $to_size['width'])
						$to_size['height'] = 0;
				}

				$best_fit = false;
				break;
			case Attachment::GEOMETRY_SCALE_EMPHATIC:
				$best_fit = false;
				break;
			case Attachment::GEOMETRY_SCALE_IF_GREATER:
				if(!($width > $to_size['width'] || $height > $to_size['height']))
					$to_size = false;

				$best_fit = true;
				break;
			case Attachment::GEOMETRY_SCALE_IF_BOTH_GREATER:
				if($width <= $to_size['width'] || $height <= $to_size['height'])
					$to_size = false;

				$best_fit = true;
				break;
			case Attachment::GEOMETRY_SCALE_AREA:
				if(($area = $width * $height) > $to_size['area']) {
					$perc = $to_size['area'] / $area;
					$to_size['width']  = $width * $perc;
					$to_size['height'] = $height * $perc;
				} else {
					$to_size = false;
				}
				break;
			case Attachment::GEOMETRY_CROP:
				$crop = true;
				break;
			default:
				throw new \P3\Exception\ActiveRecordException("Unknown scale handler set in attachment style");
		}


		if($to_size)
			$to_size['opt'] = array('crop' => $crop, 'best_fit' => $best_fit);

		return $to_size;
	}

	/**
	 * This is the starting step for _getSizeOptions().  It takes the size string,
	 * strips any non-usable, and no longer needed chars, and returns an array
	 * with a width key, and height key.
	 * 
	 * @param string $str size string to parse
	 * 
	 * @return array to_size array assoc. array containing height and width keys
	 */
	private function _parseSizeString($str)
	{
		$ret = array();

		if(FALSE !== strpos($str, '@')) {
			$ret['area'] = str_replace('@', '', $str);
		} else {
			$is_perc = strpos($str, '%');
			$str = str_replace(array('%', '>', '<', '^', '#', '!'), '', $str);

			$ex = explode('x', $str);

			if(count($ex) == 1) {
				if(!$is_perc) {
					$ret['width'] = $ex[0];
					$ret['height'] = null;
				} else {
					$ret['width']  = $ex[0];
					$ret['height'] = $ret['width'];
				}
			} else {
				$ret['width']  = $ex[0];
				$ret['height'] = $ex[1];
			}
		}

		return $ret;
	}
}

?>