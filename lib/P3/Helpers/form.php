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

		if(isset($this->_options['print']) && $this->_options['print'])
			$this->start();
	}

	public function checkBox($field, array $options = array())
	{
		$labelBefore = empty($options['labelBefore']) ? false : $options['labelBefore'];

		$id    = $this->_modelField.'-'.$this->_model->id().'-'.$field;
		$label = '<label for="'.$id.'">'.str::toHuman($field, true).'</label>';
		$input = '<input id="'.$id.'" type="checkbox" value="1" name="'.$this->_getFieldName($field).'"'.(($this->_model->{$field}) ? ' checked="checked"' : '').' />';

		/* Little trick I thought of.. This way 0 is sent if the box is not checked, but overridden to 1 if it is *bows* */
		$this->hiddenField($field, array('value' => 0));

		if($labelBefore)
			echo $label.$input;
		else
			echo $input.$label;
	}

	public function collectionSelect($field, $collection, $display_key, array $options = array())
	{
		$value_key = !isset($options['value_key']) ? $collection[0]->pk() : $options['value_key'];
		$select_options = array();

		foreach($collection as $model)
			$select_options[$model->{$value_key}] = $model->{$display_key};


		$this->select($field, $select_options, $options);
	}

	public function end()
	{
		self::close();
	}

	public function hiddenField($field, array $options = array())
	{
		$val = !isset($options['value']) ? $this->_model->{$field} : $options['value'];
		$input = '<input type="hidden" name="'.$this->_getFieldName($field).'" value="'.$val.'" />';
		echo $input;
	}

	public function select($field, array $select_options = array(), array $options = array())
	{
		$options = array_merge($options, array("selected" => $this->_model->{$field}));
		html::select($this->_getFieldName($field), $select_options, $options);
	}

	public function textArea($field, array $options = array())
	{
		$cols  = empty($options['cols']) ? 45 : $options['cols'];
		$rows  = empty($options['rows']) ? 10 : $options['rows'];

		$input = '<textarea cols="'.$cols.'" rows="'.$rows.'" name="'.$this->_getFieldName($field).'">'.$this->_model->{$field}.'</textarea>';
		echo $input;
	}

	public function textField($field, array $options = array())
	{
		$input = '<input type="text" name="'.$this->_getFieldName($field).'" value="'.$this->_model->{$field}.'" />';
		echo $input;
	}

	public function passwordField($field, array $options = array())
	{
		$input = '<input type="password" name="'.$this->_getFieldName($field).'" value="'.$this->_model->{$field}.'" />';
		echo $input;
	}

	public function start()
	{
		self::tag($this->_getUri(), $this->_options);
	}

//Private
	private function _getFieldName($field)
	{
		return $this->_modelField.'['.$field.']';
	}

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

	private function _inspect()
	{
		$this->_modelField = str::fromCamelCase(get_class($this->_model));
		$this->_action     = $this->_model->isNew() ? 'create' : 'update';
		$this->_uri        = $this->_getUri();
	}

//Static
	public static function close()
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

	public static function tag($url, array $options = array())
	{
		$form = '<form method="'.(isset($options['method']) ? $options['method'] : 'post').'"';
		$form .= (isset($options['multipart']) && $options['multipart']) ? ' enctype="form/multipart"' : '';
		$form .= ' action="'.$url.'">';
		echo $form;
	}
}
?>