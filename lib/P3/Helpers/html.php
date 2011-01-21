<?php
/**
 * html
 *
 * A helper class, mainly for views
 *
 * @author Tim Frazier <tim.frazier@gmail.com>
 */
abstract class html {
	/**
	 * Array of elements that close with '/>' instead of '>'
	 * @var array
	 */
	public static $_specialClose = array('base', 'input', 'link', 'img');

	/**
	 * @todo Make base work in nested dirs
	 */
	public static function base()
	{
		$base = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ':'.$_SERVER['SERVER_PORT'] : '').'/';
		echo '<base href="'.$base.'" />';
	}

	/**
	 * Generates and Renders select menu
	 *
	 * @param string $name Name for <select> element
	 * @param array $html_options Array of value=>display for <select>'s <options>
	 * @param array $options Options
	 */
	public static function select($name, $html_options, array $options = array())
	{
		$select  = '<select name="'.$name.'">';
		$select .= self::selectOptions($html_options, $options);
		$select .= '</select>';
		echo  $select;
	}

	/**
	 * Generates and returns <options>'s for <select>
	 *
	 * @param array $html_options Array of value => display for select options
	 * @param array $options
	 * @return string
	 */
	public static function selectOptions($html_options, array $options = array())
	{
		if(isset($options['blankOption'])) {
			$options_str = !($options['blankOption']) ? '' : '<option value="">'.$options['blankOption'].'</option>';
		} else {
			$options_str = '<option value="">Choose one</option>';
		}

		if(!empty($html_options)) {
			foreach($html_options as $k => $v) {
				$options_str .= '<option'.((isset($options['selected']) && $options['selected'] == $k) ? ' selected="selected"' : '').' value="'.$k.'">'.$v.'</option>';
			}
		}
		return $options_str;
	}

	public static function _t($tagName, array $html_attrs = array())
	{
		$tagName = strtolower($tagName);
		$element = '<'.$tagName;

		foreach($html_attrs as $k => $v)
			$element .= ' '.$k.'="'.$v.'"';

		$element .= in_array($tagName, self::$_specialClose) ? '/>' : '>';

		return $element;
	}
}

?>
