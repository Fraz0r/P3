<?php

namespace P3\Model\Exception;

/**
 * Description of attribute_no_exist
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class AttributeNoExist extends \P3\Exception\ModelException
{
	public function __construct($model, $attribute)
	{
		parent::__construct('%s Model doesn\'t contain attribute: %s', array($model, $attribute), 500);
	}
}

?>