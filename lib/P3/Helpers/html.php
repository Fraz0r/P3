<?php
/**
 * html
 *
 * A helper class, mainly for views
 *
 * @author Tim Frazier <tim@essential-elements.net>
 */
abstract class html {
	const ATTR_FORM_TYPE   = 1;
	const ATTR_FORM_METHOD = 2;

	const FORM_TYPE_STANDARD = 101;
	const FORM_TYPE_AJAX     = 102;

	const FORM_METHOD_GET  = 'get';
	const FORM_METHOD_POST = 'post';

	public static function form_start($action, array $options = array())
	{
		$method = isset($options[self::ATTR_FORM_METHOD]) ? $options[self::ATTR_FORM_TYPE] : self::FORM_METHOD_POST;
		$xhr    = (bool)(isset($options[self::ATTR_FORM_TYPE]) && $options[self::ATTR_FORM_TYPE] == self::FORM_TYPE_AJAX);
		return '<form'.(($xhr) ? ' class="P3-ajaxform"':'').' method="'.$method.'" action="'.$action.'">';
	}

	public static function form_end()
	{
		return '</form>';
	}

	public static function hidden_field($name, $val)
	{
		return '<input type="hidden" name="'.$name.'" value="'.$val.'" />';
	}

	public static function link_to($name, $location, $args = array(), $get = array())
	{
		return '<a href="'.P3_Loader::createURI($location, $args, $get).'">'.$name.'</a>';
	}

	public static function select($name, $html_options)
	{
		$select  = '<select name="'.$name.'">';
		$select .= self::select_options($html_options);
		$select .= '</select>';
		return $select;
	}

	public static function select_options($html_options)
	{
		$options = '';
		if(!empty($html_options)) {
			foreach($html_options as $k => $v) {
				$options .= '<option value="'.$k.'">'.$v.'</option>';
			}
		}
		return $options;
	}
}

?>
