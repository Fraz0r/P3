<?php


P3_Loader::loadHelper('str');
P3_Loader::loadHelper('html');

class form
{
	/**
	 * Action
	 * @var string 
	 */
	private $_action = null;

	/**
	 * Model
	 * @var P3_Model_DB
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
	public function  __construct(P3_Model_DB $model, array $options = array())
	{
		$this->_model   = $model;

		foreach($options as $k => $v)
			$this->_options[$k] = $v;

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

		$attrs = array_merge(array(
			'id'      => $id,
			'type'    => 'checkbox',
			'value'   => 1,
			'name'    => $this->_getFieldName($field),
			'checked' => $this->_model->{$field} ? 'checked' : ''
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

	/**
	 * Closes <form> tag
	 */
	public function close()
	{
		self::end();
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

		$class = $this->_modelClass;
		if(isset($class::$_validatesPresence[$field]) || in_array($field, $class::$_validatesPresence)) {
			$options['class'] = isset($options['class']) ? $options['class'].' required' : 'required';
		}

		echo html::_t('label', $options).$text.'</label>';
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
		$options['selected'] = $this->_model->{$field};
		$options['id']       = $this->_getFieldID($field);
		html::select($this->_getFieldName($field), $select_options, $options);
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

		$input = '<textarea id="'.$this->_getFieldID($field).'" cols="'.$cols.'" rows="'.$rows.'" name="'.$this->_getFieldName($field).'">'.$this->_model->{$field}.'</textarea>';
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
		echo html::_t('input',
				array_merge(array(
					'type'  => 'text',
					'id'    => $this->_getFieldID($field),
					'name'  => $this->_getFieldName($field),
					'value' => $this->_model->{$field}
				), $options)
		);
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
	public function submitField($display, array $options = array())
	{
		$options['value'] = $display;
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

		if($validate) {
			$class = $this->_modelClass;
			$js    = "var flag = true; ";
			$js    = "var req = ".json_encode($class::$_validatesPresence).'; ';
			$js    .= "for(var i = 0; i < this.elements.length; i++) { for(var j = 0; j < req.length; j++) { if('".$this->_modelField."[' + req[j] + ']' ==  this.elements[i].name){ if(this.elements[i].value == ''){ flag = false; $(this.elements[i]).addClass('error').change(function(){ if(this.value != '') $(this).removeClass('error'); }); break; } } } } ";
			$js    .= "if(!flag) alert('Please fill in required fields (*)'); ";
			$js    .= "return flag;";
			$this->_options['onsubmit'] = str_replace('"', '\'', $js);
		}

		self::tag($this->_getUri(), $this->_options);
	}

//Private
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
			$uri = P3_Router::getGlobalRoute()->path;
			$uri = str_replace(':controller', $this->_model->getController(), $uri);

			$uri = preg_replace("!\[?/:action\]?!", '/'.$this->_action, $uri);
			$uri = preg_replace("!\[?/:id\]?!", ($this->_action == 'create') ? '' : "/".$this->_model->id(), $uri);

			$this->_uri = $uri;
		}

		return $this->_uri;
	}

	/**
	 * Inspects passed arguments and gathers required information
	 */
	private function _inspect()
	{
		$this->_modelClass = get_class($this->_model);
		$this->_modelField = str::fromCamelCase($this->_modelClass);
		$this->_action     = $this->_model->isNew() ? 'create' : 'update';
		$this->_uri        = $this->_getUri();
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
	 * @param P3_Model_DB $model
	 * @param array $options
	 * @param bool $print
	 * @return form 
	 */
	public static function forModel(P3_Model_DB $model, array $options = array(), $print = true)
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
		echo html::_t('form',
				array_merge(array(
					'method'  => isset($options['method']) ? $options['method'] : 'post',
					'action' => $url
				), $options)
		);
	}
}
?>