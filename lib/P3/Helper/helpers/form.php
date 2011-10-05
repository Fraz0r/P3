<?php

use P3\Router as Router;

/**
 * Form Helper
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Helper
 * @version $Id$
 */
class form extends P3\Helper\Base
{
	/**
	 * Action
	 * @var string
	 */
	private $_action = null;

	private $_namespaces = array();

	/**
	 * Model
	 * @var P3\ActiveRecord\Base
	 */
	private $_model = null;

	/**
	 * Field name for form
	 * @var string
	 */
	private $_modelClass = null;

	/**
	 * Field name for form
	 * @var string
	 */
	private $_modelField = null;

	/**
	 * Options
	 * @var array
	 */
	private $_options = array(
		'method' => 'post'
	);

	/**
	 * URI
	 * @var str
	 */
	private $_uri = null;

//Public
	public function  __construct($model, array $options = array())
	{
		/* If array is passed, we're looking for a specific namespace */
		if(is_array($model)) {
			$tmp = $model;
			$model = array_pop($tmp);
			$this->_namespaces = $tmp;
		}

		if(!isset($options['validate']))
			$options['validate'] = true;

		$this->_model   = $model;

		$this->_options = $options;

		if(isset($options['url'])) {
			$this->_uri = $options['url'];
			unset($this->_options['url']);
		}

		$this->_inspect();

		$class = $this->_modelClass;
		if(isset($class::$_hasAttachment) && count($class::$_hasAttachment)) $this->_options['multipart'] = true;

		if(isset($this->_options['print']) && $this->_options['print']) {
			unset($this->_options['print']);
			$this->open();
		}
	}

	/**
	 * Renders a checkbox form option for the model's field
	 *
	 * @param string $field Field to use for naming/value
	 * @param array $options
	 */
	public function checkBox($field, array $options = array())
	{
		if($this->_fieldRequired($field) && $this->validate())
			$options['class'] = $this->_getValidationClassForField($field, isset($options['class']) ? $options['class'] : null);

		$printLabel = empty($options['include_label']) ? false : (bool)$options['include_label'];
		$labelBefore = empty($options['labelBefore']) ? false : $options['labelBefore'];
		unset($options['labelBefore']);

		$id    = $this->_getFieldID($field);
		$label = '<label for="'.$id.'">'.str::toHuman($field, true).'</label>';

		$checked = !is_null($this->_model->{$field}) && (bool)$this->_model->{$field};
		if($checked)
			$options['checked'] = 'checked';

		$attrs = array_merge(array(
			'id'      => $id,
			'type'    => 'checkbox',
			'value'   => 1,
			'name'    => $this->_getFieldName($field)
		), $options);

		$input = html::_t('input', 	$attrs);

		/* Little trick I thought of.. This way 0 is sent if the box is not checked, but overridden to 1 if it is *bows* */
		$this->hiddenField($field, array('value' => 0));

		if($printLabel) {
			if($labelBefore)
				echo $label.$input;
			else
				echo $input.$label;
		} else {
			echo $input;
		}
	}

	/**
	 * Renders a select menu for a given field within the model, using a collection
	 * of models as the <option>'s
	 *
	 * @param string $field Field within model to render select for
	 * @param array $collection Array of models for building <option>'s
	 * @param string $display_key Field within collection models to use for the dislplay of the option
	 * @param array $options
	 */
	public function collectionSelect($field, $collection, $display_key, array $options = array())
	{
		$select_options = array();

		if(count($collection)) {
			$value_key = !isset($options['value_key']) ? $collection[0]->pk() : $options['value_key'];
			foreach($collection as $model)
				$select_options[$model->{$value_key}] = $model->send($display_key);
		}

		$options['name'] = isset($options['name']) ? $options['name'] : $this->_getFieldName($field);

		$options['selected'] = $this->_model->{$field};

		\html::collectionSelect($collection, $display_key, $options);
	}

	public function fieldsFor($child_name, array $options = array())
	{
		$model = $this->_model->{$child_name};
		return new self($model, $options, false);
	}

	public function file($field, array $options = array())
	{
		$this->_options['multipart'] = true;

		if($this->_fieldRequired($field) && $this->validate())
			$options['class'] = $this->_getValidationClassForField($field, isset($options['class']) ? $options['class'] : null);

		$attrs = array(
			'type' => 'file',
			'name' => isset($options['name']) ? $options['name'] : $this->_getFieldName($field)
		);

		if(isset($options['id']))
			$attrs['id'] = $options['id'];

		echo \html::_t('input', $attrs);
	}

	/**
	 * Closes <form> tag
	 */
	public function close()
	{
		self::end();
	}

	public function dateSelect($field, array $options = array())
	{
		$options['blankOption'] = isset($options['blankOption']) ? $options['blankOption'] : false;
		$parts = array('year' => 1, 'month' => 2, 'day' => 3, 'hour' => 4, 'minute' => 5, 'second' => 6, 'ampm' => 7);
		$order = isset($options['order']) ? $options['order'] : array('month', 'day', 'year');

		if(isset($options['include_blank']) && $options['include_blank'])
			$options['blankOption'] = ' ';

		if(!is_null($this->_model->{$field}) && FALSE !== ($vals = preg_match('/^([\d]{4})-([\d]{2})-([\d]{2}).*/', $this->_model->{$field}, $matches))) {
			list($full, $y, $m, $d) = array_map(function($v){ static $x = 0; return ($x++ != 0) ? (int)$v : $v;}, $matches);
		} else {
			$y = $m = $d = null;
		}

		$ret = '';
		foreach($order as $item) {
			switch($item) {
				case 'year':
					if(!isset($options['use_text_year']) || !$options['use_text_year']){
						$func = $item.'sForSelect';
						$select_options = \date::$func($options);
						$options['selected'] = $y;
						$ret .= self::select($field.'('.$parts[$item].'i)', $select_options, $options);
						continue;
					}

					$ret .= $this->textField($field.'('.$parts[$item].'i)', array_merge(array('value' => $y, 'maxlength' => 4, 'size' => 3)));
				break;
				default:
					switch($item) {
						case 'month':
							$options['selected'] = $m;
							break;
						case 'day':
							$options['selected'] = $d;
							break;
					}

					$func = $item.'sForSelect';
					$select_options = \date::$func($options);
					$ret .= self::select($field.'('.$parts[$item].'i)', $select_options, $options);
			}
		}

		echo $ret;
	}

	/**
	 * Renders hidden field for field in model
	 *
	 * @param string $field
	 * @param array $options
	 */
	public function hiddenField($field, array $options = array())
	{
		$options['value'] = !isset($options['value']) ? $this->_model->{$field} : $options['value'];

		if($this->_fieldRequired($field) && $this->validate())
			$options['class'] = $this->_getValidationClassForField($field, isset($options['class']) ? $options['class'] : null);

		echo html::_t('input', array_merge(
			array(
				'type'  => 'hidden',
				'id'    => $this->_getFieldID($field),
				'name'  => $this->_getFieldName($field)
			), $options)
		);
	}

	/**
	 * Returns id for field
	 *
	 * @param string $field Field Name
	 */
	public function id($field)
	{
		return $this->_getFieldID($field);
	}

	/**
	 * Renders <label> for $field
	 *
	 * @param string $field Field the label is for
	 * @param string $text Dislplay text for label tag
	 * @param array $options Options for rendering
	 */
	public function labelFor($field, $text = null, array $options = array())
	{
		$options['for'] = $this->_getFieldID($field);
		$text = is_null($text) ? str::toHuman($field, true) : $text;

		$required = false;
		if($this->validate() && ((isset($options['required']) && $options['required']) || $this->_fieldRequired($field))) {
			$options['class'] = isset($options['class']) ? $options['class'].' required' : 'required';
			$required = true;
		}

		$ret = html::_t('label', $options).$text.'</label>';

		echo ($required ? '<span class="req-astr">*</span>':'').$ret;
	}

	public function monthSelect($field, array $options = array())
	{
		return $this->select($field, date::monthsForSelect(), array('blankOption' => false));
	}

	/**
	 * Renders <select> menu for field in model, using assoc. array as value => display
	 *
	 * @param string $field Field in model to set value
	 * @param array $select_options
	 * @param array $options
	 */
	public function select($field, array $select_options = array(), array $options = array())
	{
		$options['selected'] = isset($options['selected']) ? $options['selected'] : $this->_model->{$field};
		$options['id']       = isset($options['id']) ? $options['id'] : $this->_getFieldID($field);

		if($this->_fieldRequired($field) && $this->validate())
			$options['class'] = $this->_getValidationClassForField($field, isset($options['class']) ? $options['class'] : null);

		html::select($this->_getFieldName($field), $select_options, $options);
	}

	/**
	 * Renders a state drop down menu
	 *
	 * @param string $field Field in model to set value
	 * @param array $options Options - valFormat (geo::STATE_FORMAT_*): format for <option> value.  dispFormat: format for display of <option>
	 */
	public function stateSelect($field, array $options = array())
	{
		$val_format = isset($options['valFormat']) ? $options['valFormat'] : geo::STATE_FORMAT_ABR;
		$disp_format = isset($options['dispFormat']) ? $options['dispFormat'] : geo::STATE_FORMAT_FULL;

		$select_options = array_combine(geo::getStates($val_format), geo::getStates($disp_format));
		$this->select($field, $select_options, $options);
	}

	/**
	 * Renders <textarea> for field in model
	 *
	 * @param string $field Field in model to use for name/value
	 * @param array $options
	 */
	public function textArea($field, array $options = array())
	{
		$cols  = empty($options['cols']) ? 45 : $options['cols'];
		$rows  = empty($options['rows']) ? 10 : $options['rows'];
		$id    = isset($options['id']) ? $options['id'] : $this->_getFieldID($field);

		if(isset($options['style']))
			$style = $options['style'];

		if (!isset($options['class'])) {
			if($this->_fieldRequired($field) && $this->validate())
				$options['class'] = $this->_getValidationClassForField($field, isset($options['class']) ? $options['class'] : null);
			else
				$options['class'] = '';
		}

		$input = '<textarea id="'.$id.'" cols="'.$cols.'" rows="'.$rows.'" name="'.$this->_getFieldName($field).'" class="'.$options['class'].'"'.(isset($style) ? ' style="'.$style.'"' : '').'>'.$this->_model->{$field}.'</textarea>';
		echo $input;
	}

	/**
	 * Renders <input[:type => text]> for field in model
	 *
	 * @param string $field Field in model for name/value
	 * @param array $options
	 */
	public function textField($field, array $options = array())
	{
		$val = isset($options['value']) ? $options['value'] : $this->_model->{$field};

		if($this->_fieldRequired($field) && $this->validate())
			$options['class'] = $this->_getValidationClassForField($field, isset($options['class']) ? $options['class'] : null);

		if(isset($options['format'])) {
			if($options['format'] == 'phone') {
				$val = preg_replace('/([^\d])/', '', $val);

				if(preg_match('/([\d]{3})([\d]{3})([\d]{4})([\d]*)/', $val, $m)) {
					$val = '('.$m[1].') '.$m[2].'-'.$m[3];
					if(!empty($m[4]))
						$val .= ' ext. '.$m[4];
				}

				$options['onkeyup'] = "var ev = event || window.event; if(event.keyCode != 8){ var v = this.value.replace(/([^\d])/g, ''); m = v.match(/([\d]{3})([\d]{1,3})?([\d]{1,4})?([\d]*)?/); if(m) { this.value = '(' + m[1]; if(m[1].length == 3) this.value += ') '; if(m[2]) this.value += m[2]; if(m[2].length == 3) this.value += '-'; if(m[3]) this.value += m[3]; if(m[4] && m[3].length == 4) this.value += ' ext ' + m[4]; } }";
			}
			unset($options['format']);
		}

		echo html::_t('input',
				array_merge(array(
					'type'  => 'text',
					'id'    => $this->_getFieldID($field),
					'name'  => $this->_getFieldName($field),
					'value' => $val
				), $options)
		);
	}

	public function timezoneSelect($field, array $options = array())
	{
		$this->select($field, \date::timezoneForSelect());
	}

	public function urlField($field, array $options = array())
	{
		$options['class'] = isset($options['class']) ? $options['class'].' url' : 'url';
		$this->textField($field, $options);
	}

	/**
	 * Renders <input[:type => password]> for field in model
	 *
	 * @param string $field Field in model for name/value
	 * @param array $options
	 */
	public function passwordField($field, array $options = array())
	{
		if($this->_fieldRequired($field) && $this->validate())
			$options['class'] = $this->_getValidationClassForField($field, isset($options['class']) ? $options['class'] : null);

		echo html::_t('input',
				array_merge(array(
					'type'  => 'password',
					'name'  => $this->_getFieldName($field),
					'id'    => $this->_getFieldID($field),
					'value' => $this->_model->{$field}
				), $options)
		);
	}

	public function phoneField($field, array $options = array())
	{
		$options['format'] = 'phone';

		$this->textField($field, $options);
	}

	/**
	 * Renders <input[:type => submit]>
	 *
	 * @param string $display Value for button
	 * @param array $options
	 */
	public function submitField($display, $submitting_text = null, array $options = array())
	{
		$options['value'] = $display;
		if($submitting_text != null) {
			$js = "$(this).val('{$submitting_text}'); this.disabled = true; this.form.submit();";
			$options['onclick'] = isset($options['onclick']) ? $js.' '.$options['onclick'] : $js;
		}

		echo html::_t('input',
				array_merge(array(
					'type'  => 'submit',
				), $options)
		);
	}

	/**
	 * Renders <form> tag
	 */
	public function open()
	{
		self::tag($this->_getUri(), $this->_options);
	}

	public function setFieldName($name)
	{
		$this->_modelField = $name;
	}

	public function validate()
	{
		return($this->_options['validate']);
	}

	public function yearSelect($field, array $options = array())
	{
		return $this->select($field, date::yearsForSelect($options), array('blankOption' => false));
	}

//- Private
	private function _fieldRequired($field)
	{
		$class = $this->_modelClass;
		return(isset($class::$_validatesPresence[$field]) || in_array($field, $class::$_validatesPresence));
	}

	/**
	 * Generates id attribute for form input
	 *
	 * @param sting $field Field name
	 * @return string ID for html attr:id
	 */
	private function _getFieldID($field)
	{
		return $this->_modelField.'-'.(!$this->_model->isNew() ? $this->_model->id().'-' : '').$field;
	}

	/**
	 * Generates name for form input
	 *
	 * @param string $field Field to generate name for
	 * @return string Name for form input
	 */
	private function _getFieldName($field)
	{
		return $this->_modelField.'['.$field.']';
	}

	/**
	 * Generates/Returns URI for <form>
	 *
	 * @return string  URI for <form>'s action
	 */
	private function _getUri()
	{
		if(empty($this->_uri)) {
			$router = P3::getRouter();
			$controller = $this->_model->getController();

			if(count($this->_namespaces))
				$controller = implode('/', $this->_namespaces).'/'.$controller;

			if(!$controller)
				throw new \P3\Exception\HelperException("Cant build URI for form because I don't know what controller belongs to %s", array($this->_model));

			$action = $this->_model->isNew() ? 'create' : 'update';
			$route  = $router::reverseLookup($controller, $action);


			if(!$route)
				throw new \P3\Exception\HelperException("Cant build URI for form because there is no create route for %s#%s.  Create one in routes.php", array($controller, $action));

			$this->_options['method'] = $route->getMethod();

			$uri = $route($this->_model->id());

			$this->_uri = $uri;
		}

		return $this->_uri;
	}

	/**
	 * Generates the class name, including existing [if passed], based on
	 * validtions set in the model.  These are design to work with the
	 * sweet JQ plugin - jQuery Validate
	 *
	 * TODO: Validations Not Current Implemented:
	 * 			validatesAlpha
	 * 			validatesAlphaNum
	 * 			validatesLength
	 *
	 * @param string $field field name
	 * @param string,null $existing existing class [string], if any
	 *
	 * @return string class name attribute
	 */
	private function _getValidationClassForField($field, $existing = null)
	{
		$classes = array();

		$model_class = $this->_modelClass;

		if(in_array($field, $model_class::$_validatesEmail))
			$classes[] = 'email';
		if(in_array($field, $model_class::$_validatesNum))
			$classes[] = 'number';
		if(in_array($field, $model_class::$_validatesPresence))
			$classes[] = 'required';

		$class = implode(' ', $classes);
		return is_null($existing) ? $class : $existing.' '.$class;
	}

	/**
	 * Inspects passed arguments and gathers required information
	 */
	private function _inspect()
	{
		$this->_modelClass = is_subclass_of($this->_model, 'P3\ActiveRecord\Collection\Base') ? $this->_model->getContentClass() : get_class($this->_model);
		$this->_modelField = isset($this->_options['as']) ? $this->_options['as'] : str::fromCamelCase($this->_modelClass);
		$this->_action     = $this->_model->isNew() ? 'create' : 'update';
		$this->_id         = ($this->_model->isNew() ? 'new-' : 'edit-').str::fromCamelCase($this->_modelClass);
		if(!$this->_model->isNew()) $this->_id .= '-'.$this->_model->id();

		if(!isset($this->_options['id'])) $this->_options['id'] = $this->_id;
	}

//Static
	/**
	 * Renders </form>
	 */
	public static function end()
	{
		echo '</form>';
	}

	/**
	 * Returns form helper for model
	 *
	 * @param P3\ActiveRecord\Base $model
	 * @param array $options
	 * @param bool $print
	 * @return form
	 */
	public static function forModel($model, array $options = array(), $print = true)
	{
		if($print)
			$options = array_merge(array('print' => true), $options);

		return new self($model, $options);
	}

	public static function fieldsForModel($model, array $options = array())
	{
		return new self($model, $options);
	}

	/**
	 * Renders <form> tag
	 *
	 * @param string $url URI For form's action
	 * @param array $options
	 */
	public static function tag($url, array $options = array())
	{
		if(isset($options['multipart'])) {
			$options['enctype'] = 'multipart/form-data';
			unset($options['multipart']);
		}

		$options['method'] = isset($options['method']) ? $options['method'] : 'post';

		if(in_array($options['method'], array('put', 'delete'))) {
			$hidden_method  = $options['method'];
			$options['method'] = 'post';
		}

		$method = $options['method'];

		echo html::_t('form',
				array_merge(array(
					'method'  => $method,
					'action' => $url
				), $options)
		);

		if(isset($hidden_method)) {
			echo '<input type="hidden" name="_method" value="'.$hidden_method.'" />';
		}
	}
}
?>
