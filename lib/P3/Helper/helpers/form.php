<?php


\P3\Loader::loadHelper('str');
\P3\Loader::loadHelper('html');

use P3\Router as Router;

class form extends P3\Helper\Base
{
	/**
	 * Action
	 * @var string
	 */
	private $_action = null;

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
		$this->_model   = $model;

		$this->_options = $options;

		$this->_uri = isset($options['url']) ? $options['url'] : null;

		$this->_inspect();

		$class = $this->_modelClass;
		if(count($class::$_hasAttachment)) $this->_options['multipart'] = true;

		if(isset($this->_options['print']) && $this->_options['print'])
			$this->open();
	}

	/**
	 * Renders a checkbox form option for the model's field
	 *
	 * @param string $field Field to use for naming/value
	 * @param array $options
	 */
	public function checkBox($field, array $options = array())
	{
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

		if($labelBefore)
			echo $label.$input;
		else
			echo $input.$label;
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
		$value_key = !isset($options['value_key']) ? $collection[0]->pk() : $options['value_key'];
		$select_options = array();

		foreach($collection as $model)
			$select_options[$model->{$value_key}] = $model->{$display_key};

		$this->select($field, $select_options, $options);
	}

	public function fieldsFor($child_name, array $options = array())
	{
		$model = $this->_model->{$child_name};
		return new self($model, $options, false);
	}

	public function file($field, array $options = array())
	{
		$this->_options['multipart'] = true;

		echo '<input type="file" name="'.$this->_getFieldName($field).'" />';
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

		$ret = '';
		foreach($order as $item) {
			switch($item) {
				case 'year':
					if(!isset($options['use_text_year']) || !$options['use_text_year']){
						$func = $item.'sForSelect';
						$select_options = \date::$func($options);
						$ret .= self::select($field.'('.$parts[$item].'i)', $select_options, $options);
						continue;
					}

					$ret .= $this->textField($field.'('.$parts[$item].'i)', array_merge(array('size' => 3)));
				break;
				default:
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
		if($this->_fieldRequired($field) && (!isset($this->_options['noValidate']) || !$this->_options['noValidate'])) {
			$options['class'] = isset($options['class']) ? $options['class'].' required' : 'required';
			$required = true;
		}

		$ret = html::_t('label', $options).$text.'</label>';

		echo ($required ? '<span class="req-astr">*</span>':'').$ret;
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

		$input = '<textarea id="'.$id.'" cols="'.$cols.'" rows="'.$rows.'" name="'.$this->_getFieldName($field).'">'.$this->_model->{$field}.'</textarea>';
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

	/**
	 * Renders <input[:type => password]> for field in model
	 *
	 * @param string $field Field in model for name/value
	 * @param array $options
	 */
	public function passwordField($field, array $options = array())
	{
		echo html::_t('input',
				array_merge(array(
					'type'  => 'password',
					'name'  => $this->_getFieldName($field),
					'id'    => $this->_getFieldID($field),
					'value' => $this->_model->{$field}
				), $options)
		);
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
		if(isset($this->_options['noValidate'])) {
			$validate = false;
			unset($this->_options['noValidate']);
		} else {
			$validate = true;
		}

		$class = $this->_modelClass;
		if($validate && count($class::$_validatesPresence)) {
			$scrollError = (isset($this->_options['errorScrolling']) && $this->_options['errorScrolling']) ? true : false;
			$js    = "var flag = true; ";
			$js    .= "var req = ".json_encode($class::$_validatesPresence).'; var e = 0; ';
			$js    .= "for(var i = 0; i < this.elements.length; i++) { for(var j = 0; j < req.length; j++) { if('".$this->_modelField."[' + req[j] + ']' ==  this.elements[i].name){ if(this.elements[i].value == ''){ e++; flag = false; ".(($scrollError) ? "if(e==1) $.scrollTo($(this.elements[1]), {duration: 1000});" : "")." $(this.elements[i]).addClass('error').change(function(){ if(this.value != '') $(this).removeClass('error'); }); break; } } } } ";
			$js    .= "if(!flag) alert('Please fill in required fields (*)'); ";
			$js    .= "return flag;";

			if(!isset($this->_options['onsubmit']))
				$this->_options['onsubmit'] = str_replace('"', '\'', $js);
			else {
				$this->_options['onsubmit'] = $this->_options['onsubmit'].' '.str_replace('"', '\'', $js);
			}
		}

		self::tag($this->_getUri(), $this->_options);
	}

	public function setFieldName($name)
	{
		$this->_modelField = $name;
	}

//Private
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
		return $this->_modelField.'-'.$this->_model->id().'-'.$field;
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
