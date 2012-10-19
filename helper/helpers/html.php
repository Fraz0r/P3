<?php
/**
 * HTML Helpers
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 * @package P3\ActiveRecord\Association
 * @version $Id$
 */
abstract class html extends P3\Helper\Base
{
	public static function _($tag_name, array $attrs = [], $oneline = false)
	{
		$ret = '<'.$tag_name;

		$attributes = [];

		foreach($attrs as $k => $v)
			$attributes[] = "{$k}=\"{$v}\"";

		if(count($attributes))
			$ret .= ' '.implode(' ', $attributes);

		if($oneline)
			$ret .= ' />';
		else
			$ret .= '>';
		
		return $ret;
	}

	public static function _c($tag_name)
	{
		return '</'.$tag_name.'>';
	}

	public static function content_tag($name, $content, array $options = [], $closure = null)
	{
		$ret = self::_($name, $options);
		$ret .= $content;
		$ret .= self::_c($name);

		return $ret;
	}

	public static function img($src, array $options = [])
	{
		if(isset($options['size'])) {
			list($width, $height) = explode('x', $options['size']);
			$options['width'] = $width;
			$options['height'] = $height;

			unset($options['height']);
		}

		$options['src'] = $src;

		return self::_('img', $options, true);
	}

	public static function javascript_tag($closure_or_options, $closure_if_options = null)
	{
		if(is_callable($closure_or_options)) {
			$closure = $closure_or_options;
			$options = [];
		} else {
			$closure = $closure_if_options;
			$options = $closure_or_options;
		}

		$ret = self::_('script', array_merge(['type' => 'text/javascript'], $options));
		if(!is_null($closure)) {
			ob_start();
			$closure();
			$ret .= ob_get_clean().self::_c('script');
		}

		return $ret;
	}

	public static function link_to($display, $location, array $url_options = [], array $html_options = [])
	{
		if($location == ':back')
			$location = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'javascript:history.back();';

		$html_options['href'] = $location;

		if(isset($url_options['method']))
			$html_options['data-method'] = $url_options['method'];
		if(isset($url_options['confirm']))
			$html_options['data-confirm'] = $url_options['confirm'];

		return self::content_tag('a', $display, $html_options);
	}
}

?>
