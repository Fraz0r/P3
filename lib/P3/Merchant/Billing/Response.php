<?php

namespace P3\Merchant\Billing;

/**
 * Description of Response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Response 
{
	private $_success      = false;
	private $_fraud_review = false;
	private $_test         = false;

	public $authorization = null;
	public $avs_result    = null;
	public $cvv_result    = null;
	public $message       = null;
	public $params        = array();

	public function __construct($success, $message, array $params = array(), array $options = array())
	{
		$this->_success = $success;
		$this->_test    = isset($options['test']) ? $options['test'] : false;

		if(isset($options['fraud_review']))
			$this->_fraud_review = $options['fraud_review'];

		$this->message = $message;
		$this->params  = $params;

		if(isset($options['authorization']))
			$this->authorization = $options['authorization'];

		if(isset($options['avs_result'])) {
			$avs = new AVSResult($options['avs_result']);
			$this->avs_result = $avs->toArray();
		}

		if(isset($options['cvv_result'])) {
			//$cvv = new CVVResult($options['cvv_result']);
			//$this->cvv_result = $cvv->toArray();
		}
	}

	public function needsFraudReview()
	{
		return $this->_fraud_review;
	}

	public function success()
	{
		return $this->wasSuccessfull();
	}

	public function wasSuccessfull()
	{
		return $this->_success;
	}

	public function wasTest()
	{
		return $this->_test;
	}
}

?>
