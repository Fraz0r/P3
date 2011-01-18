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
	 * @todo Make base work in nested dirs
	 */
	public static function base()
	{
		$base = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 ? ':'.$_SERVER['SERVER_PORT'] : '').'/';
		echo '<base href="'.$base.'" />';
	}

	public static function linkFor(P3_Model_DB $model, $text = null, $action = 'show', array $options = array())
	{
		$controller = $model->controller;
		$text       = is_null($text) ? ucfirst($action) : $text;

		/* This needs to be programmed (First route setting both a controller and action) */
		//$route      = P3_Router::getFirstGlobalRoute();
		$path = str_replace(':controller', $controller, $route['path']);
		$path = str_replace(':action', $action, $route['path']);

		if((bool)strpos($route['path'], ':id'))
			$path = str_replace(':id', $model->id, $route['path']);
		else
			$path = rtrim($path, '/').'/'.$model->id;

		echo '<a href="'.$path.'">'.$text.'</a>';
	}


	public static function select($name, $html_options, array $options = array())
	{
		$select  = '<select name="'.$name.'">';
		$select .= self::select_options($html_options, $options);
		$select .= '</select>';
		echo  $select;
	}

	public static function select_options($html_options, array $options = array())
	{
		if(isset($options['blankOption'])) {
			$options_str = is_null($options['blankOption']) ? '' : '<option value="">'.$options['blankOption'].'</option>';
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
}

?>
