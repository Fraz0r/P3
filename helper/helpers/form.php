<?php

/**
 * Form Helper
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Helper
 * @version $Id$
 */
class form extends P3\Helper\Base
{
	private $_model;
	private $_namespacing = [];
	private $_options = [];

//- Public
	// TODO:  Enhance the ways url can be controled  (accept controller, action, etc)  Right now string only.  Needs to rely on the Route object instead of this bs
	public function __construct($model, array $options = [], callable $closure = null)
	{
		if(is_array($model)) {
			$tmp = $model;
			$this->_model = array_pop($tmp);
			$this->_namespacing = $tmp;
		} else {
			$this->_model = $model;
		}

		if(isset($options['url'])) {
			$url = $options['url'];
		} else {
			$url = url::for_model($model);
		}

		if($this->_model->is_new())
			$options['method'] = 'post';
		else
			$options['method'] = 'put';

		if($this->_model->has_attachments())
			$options['multipart'] = true;

		$options = self::_parse_form_options($options);
		$options['html']['action'] = $url;

		$this->_options = $options;
	}

	public function close()
	{
		return html::_c('form');
	}

	public function error_messages(array $options = [])
	{
		if(!($count = count($this->_model->errors)))
			return false;

		$humanized_model = str::humanize(get_class($this->_model));
		$header_tag     = isset($options['header_tag']) ? $options['header_tag'] : "h2";
		$header_message = isset($options['header_message']) ? $options['header_message'] : "{$count} errors prohibited this {$humanized_model} from being saved";
		$message        = isset($options['message']) ? $options['message'] : "There were problems with the following fields:";

		$errors = $this->_model->errors->full_messages();

		// TODO:  This can be done cleaner.  with closures maybe?
		$ret = html::content_tag($header_tag, $header_message);
		$ret .= html::content_tag('p', $message);
		$ret .= html::_('ul');
		foreach($errors as $error)
			$ret .= html::content_tag('li', $error);
		$ret .= html::_c('ul');
		return $ret;
	}

	public function get_model()
	{
		return $this->_model;
	}

	public function open()
	{
		return self::tag($this->_options['html']['action'], $this->_options);
	}

//- Static
	public static function check_box($container, $field, array $options = [])
	{
		$options = array_merge(['name' => "{$container}[{$field}]", 'id' => "{$container}_{$field}"], $options);

		return self::check_box_tag($field, $options);
	}

	public static function check_box_tag($field, array $options = [])
	{
		if(isset($options['checked']) && $options['checked'])
			$options['checked'] = $options['checked'];

		$attributes = array_merge(['type' => 'checkbox', 'name' => $field, 'id' => $field, 'value' => 1], $options);
		$ret = html::_('input', ['type' => 'hidden', 'name' => $attributes['name'], 'value' => 0], true);


		$ret .= html::_('input', $attributes, true);

		return $ret;
	}

	public static function file_field($container, $field, array $options = [])
	{
		$attributes = array_merge(['name' => "{$container}[{$field}]", 'id' => "{$container}_{$field}"], $options);

		return self::file_field_tag($field, $attributes);
	}

	public static function file_field_tag($field, array $options = [])
	{
		return html::_('input', array_merge(['type' => 'file', 'name' => $field], $options));
	}

	public static function fields_for($model, array $options = [], callable $closure = null)
	{
		$options['print'] = false;

		return self::for_model($model, $options, $closure);
	}

	public static function for_model($model, array $options = [], callable $closure = null)
	{
		$form = new self($model, $options, $closure);
		$print = !isset($options['print']) || (bool)$options['print'];

		if($print) {
			$ret = $form->open();


			if(!is_null($closure)) {
				ob_start();
				$closure(new FormProxy($form, $model));
				$ret .= ob_get_clean();
				$ret .= $form->close();
			}

			return $ret;
		} else {
			return $form;
		}
	}

	public static function hidden_field($container, $field, $val, array $options = [])
	{
		return self::hidden_field_tag($field, $val, array_merge(['name' => "{$container}[{$field}]", 'type' => 'hidden'], $options));
	}

	public static function hidden_field_tag($field, $val, array $options = [])
	{
		return html::_('input', array_merge(['value' => $val, 'type' => 'hidden', 'name' => $field], $options));
	}

	public static function label($container, $for, $display = null, array $options = [])
	{
		return static::label_tag($for, $display, array_merge(['for' => $container.'_'.$for], $options));
	}

	public static function label_tag($for, $display = null, array $options = [])
	{
		if(is_null($display))
			$display = str::humanize($for, true);

		return html::_('label', array_merge(['for' => $for], $options)).$display.html::_c('label');
	}

	public static function options_for_select($select_options, array $options = [])
	{
		if(is_string($select_options))
			return $select_options;

		if(!isset($options['selected']))
			$options['selected'] = [];
		elseif(!is_array($options['selected']))
			$options['selected'] = [$options['selected']];

		if(!isset($options['disabled']))
			$options['disabled'] = [];
		elseif(!is_array($options['disabled']))
			$options['disabled'] = [$options['disabled']];

		$ret = '';
		foreach($select_options as $value => $display) {
			$selected = in_array($value, $options['selected']);
			$disabled = in_array($value, $options['disabled']);

			$attrs = ['value' => $value];

			if($selected)
				$attrs['selected'] = 'selected';
			elseif($disabled)
				$attrs['disabled'] = 'disabled';

			$ret .= html::content_tag('option', $display, $attrs);
		}

		return $ret;
	}

	public static function radio_button_tag($field, $value, array $options = [])
	{
		$attributes = array_merge(['id' => $field.'_'.$value, 'name' => $field, 'type' => 'radio', 'value' => $value], $options);

		return html::_('input', $attributes, true);
	}

	public static function select($container, $field, $select_options, array $options = [])
	{
		$options = array_merge(['name' => "{$container}[{$field}]", 'id' => "{$container}_{$field}"], $options);

		if(isset($options['multiple']) && $options['multiple'])
			$options['multiple'] = 'multiple';

		return self::select_tag($field, $select_options, $options);
	}

	public static function select_tag($field, $select_options, array $options = [])
	{
		if(!is_string($select_options))
			$select_options = self::options_for_select($select_options, $options);

		if(isset($options['include_blank']) && $options['include_blank'])
			$select_options = html::content_tag('option', is_string($options['include_blank']) ? $options['include_blank'] : '').$select_options;

		return html::content_tag('select', $select_options, arr::filter($options, ['selected', 'disabled']));
	}

	public static function submit($container, $display, array $options = [])
	{
		return static::submit_tag($display, $options);
	}

	public static function submit_tag($display, array $options = [])
	{
		return html::_('input', array_merge(['type' => 'submit', 'value' => $display], $options));
	}

	public static function tag($uri, array $options = [], callable $closure = null)
	{
		$options = self::_parse_form_options($options);

		$options['html']['action'] = $uri;

		if(!in_array($options['html']['method'], ['get', 'post'])) {
			$actual_method = $options['html']['method'];

			$options['html']['method'] = 'post';
		}

		$ret = html::_('form', $options['html']);

		if(isset($actual_method))
			$ret .= self::hidden_field_tag('_method', $actual_method);

		if(!is_null($closure)) {
			ob_start();
			$closure();
			$ret .= ob_get_clean();
			$ret .= html::_c('form');
		}

		return $ret;
	}

	public static function text_area($container, $field, $value = null, array $options = [])
	{
		$attributes = array_merge(['name' => "{$container}[{$field}]", 'id' => "{$container}_{$field}"], $options);

		return self::text_area_tag($field, $value, $attributes);
	}

	public static function text_area_tag($field, $value = null, array $options = [])
	{
		if(isset($options['size'])) {
			list($cols, $rows) = explode('x', $options['size']);
			$options['cols'] = $cols;
			$options['rows'] = $rows;
			unset($options['size']);
		}

		$ret = html::_('textarea', array_merge(['name' => $field, 'id' => $field], $options));

		if(!is_null($value))
			$ret .= $value;

		$ret .= html::_c('textarea');

		return $ret;
	}

	public static function text_field($container, $field, array $options = [])
	{
		$attributes = array_merge(['name' => "{$container}[{$field}]", 'id' => "{$container}_{$field}"], $options);

		return self::text_field_tag($field, $attributes);
	}

	public static function text_field_tag($field, array $options = [])
	{
		return html::_('input', array_merge(['name' => $field, 'type' => 'text', 'id' => $field], $options), true);
	}


	private static function _parse_form_options(&$options)
	{
		if(!isset($options['html']))
			$options['html'] = [];

		if(!isset($options['charset']))
			$options['html']['charset'] = 'UTF-8';

		if(isset($options['multipart'])) {
			if($options['multipart'])
				$options['html']['enctype'] = 'multipart/form-data';
		}

		if(!isset($options['method']))
			$options['method'] = 'post';


		$options['html']['method'] = $options['method'];

		return $options;
	}
}


class FormProxy
{
	private $_form;
	private $_model;
	private $_namespacing = [];
	private $_container;
	private $_print_pk = false;

	public function __construct($form, $model, $container = null, $_print_pk = false)
	{
		$this->_form = $form;

		if(is_array($model)) {
			$this->_model = array_pop($model);
			$this->_namespacing = $model;
		} else {
			$this->_model = $model;
		}

		$this->_container = is_null($container) ? str::from_camel(get_class($this->_model)) : $container;
	}

	public function check_box($field, array $options = [])
	{
		if($this->_model->attr_exists($field) && $this->_model->{$field})
			$options['checked'] = true;

		return form::check_box($this->_get_container(), $field, $options);
	}

	public function error_messages(array $options = [])
	{
		return $this->_form->error_messages($options);
	}

	public function fields_for($association_property, $options = null, callable $closure = null)
	{
		if(is_callable($options)) { // happens if options wasn't supplied, but closure was (shift args)
			$closure = $options;
			$options = [];
		}

		if(FALSE === ($association = $this->_model->get_association($association_property)))
			throw new \P3\Exception\ArgumentException\Invalid('FormProxy', 'association_property', 'Doesnt exist within atttached model');

		// TODO:  This really needs to be cleaned up and thoroughly tested.  It's getting messy.  Refactor!
		if(isset($options['object'])) {
			$index = isset($options['child_index']) ? $options['child_index'] : 0;

			if(is_null($closure))
				throw new \P3\Exception\ArgumentException\Invalid('FormProxy', 'closure', 'Attempting to render a single child field proxy w/o a closure.  This may be possible in the future, but my head hurts for now');

			$container = $this->_get_container().'['.$association_property.']['.$index.']';

			ob_start();
			$closure(new self(form::fields_for($options['object']), $options['object'], $container, true), $index);
			return ob_get_clean();
		}

		if(is_null($closure))
			return form::fields_for($association);

		ob_start();

		foreach($association as $k => $child){
			$container = $this->_get_container().'['.$association_property.']['.$k.']';

			//TODO:  This should probably done more eligant....
			if(!$child->is_new())
				echo form::hidden_field($container, $child->get_pk_field(), $child->id());

			$closure(new self(form::fields_for($child), $child, $container, true), $k);
		}

		return ob_get_clean();
	}

	public function file_field($field, array $options = [])
	{
		return form::file_field($this->_get_container(), $field, $options);
	}

	public function get_model()
	{
		return $this->_form->get_model();
	}

	public function label($for, $display = null, array $options = [])
	{
		return form::label($this->_get_container(), $for, $display, $options);
	}

	public function select($field, $select_options, array $options = [])
	{
		if($this->_model->attr_exists($field))
			$options['selected'] = [$this->_model->{$field}];

		return form::select($this->_get_container(), $field, $select_options, $options);
	}

	public function submit($display, array $options = [])
	{
		return form::submit($this->_get_container(), $display, $options);
	}

	public function text_area($field, array $options = [])
	{
		if($this->_model->attr_exists($field))
			$value = $this->_model->{$field};
		else
			$value = null;

		return form::text_area($this->_get_container(), $field, $value, $options);
	}

	public function text_field($field, array $options = [])
	{
		if($this->_model->attr_exists($field))
			$options['value'] = $this->_model->{$field};

		return form::text_field($this->_get_container(), $field, $options);
	}

	protected function _get_container()
	{
		return $this->_container;
	}
}
?>