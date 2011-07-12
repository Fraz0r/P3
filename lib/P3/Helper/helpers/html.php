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
	/**
	 * Array of elements that close with '/>' instead of '>'
	 * @var array
	 */
	public static $_specialClose = array('base', 'input', 'link', 'img');

	/**
	 * @var array Array of script filepaths
	 */
	private static $_jsFiles = array();

	/**
	 * @var array Array of stylesheet filepaths
	 */
	private static $_cssFiles = array();

	/**
	 * Adds a stylesheet to helper
	 *
	 * @param tring $src href for stylesheet
	 */
	public static function addCss($src)
	{
		self::$_cssFiles[] = $src;
	}

	/**
	 * Adds a script to helper
	 *
	 * @param tring $src src for script
	 */
	public static function addJs($src)
	{
		self::$_jsFiles[] = $src;
	}

	/**
	 * Renders <base> tag using _SERVER superglobal
	 *
	 * @return void
	 *
	 * @todo Make base work in nested dirs
	 */
	public static function base()
	{
		$base = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ':'.$_SERVER['SERVER_PORT'] : '').'/';
		echo '<base href="'.$base.'" />'."\n";
	}

	/**
	 * Renders base, styles, and scripts
	 *
	 * @return void
	 */
	public static function head()
	{
		//self::base();
		self::styles();
		self::scripts();
	}

	/*
	 * Renders scripts for controllers, use in <head>
	 *
	 * @return void
	 */
	public static function scripts()
	{
		foreach(self::$_jsFiles as $src)
			echo self::_t('script', array(
				'type' => "text/javascript",
				'src'  => $src
			)).'</script>'."\n";
	}

	/*
	 * Renders style links for controllers, use in <head>
	 *
	 * @return void
	 */
	public static function styles()
	{
		foreach(self::$_cssFiles as $src)
			echo self::_t('link', array(
				'rel'  => 'stylesheet',
				'type' => "text/css",
				'href'  => $src
			))."\n";
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
		$id      = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
		$class   = isset($options['class']) ? ' class="'.$options['class'].'"' : '';

		$select  = '<select name="'.$name.'"'.$id.$class.'>';
		$select .= self::selectOptions($html_options, $options);
		$select .= '</select>';
		echo  $select;
	}

	/**
	 * Renders title tag for page
	 * @param string $title Title for <title>
	 */
	public static function title($title)
	{
		echo '<title>'.$title.'</title>'."\n";
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

	/**
	 * Creates HTML tag (string) using passed tagName and html_attrs
	 *
	 * @param string $tagName HTML tag to create
	 * @param array $html_attrs HTML attributes to add to tag
	 * @return string Formatted HTML tag
	 */
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
