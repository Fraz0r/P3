<?php

namespace P3\Merchant\Billing;

/**
 * Description of Response
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Response 
{
	/**
	 * Was successful?
	 * 
	 * @var boolean
	 */
	private $_success      = false;

	/**
	 * Under fraud review?
	 * 
	 * @var booelan 
	 */
	private $_fraud_review = false;

	/**
	 * Was test?
	 * 
	 * @var boolean
	 */
	private $_test         = false;

	/**
	 * Reference number for transaction
	 * 
	 * @var string
	 */
	public $authorization = null;

	/**
	 * AVS Result from association
	 * 
	 * @var array 
	 */
	public $avs_result    = null;

	/**
	 * CVV result from association
	 * 
	 * @var array 
	 */
	public $cvv_result    = null;

	/**
	 * Message from association
	 * 
	 * @var string 
	 */
	public $message       = null;

	/**
	 * Additional params
	 * 
	 * @var array 
	 */
	public $params        = array();

//- Public
	/**
	 * Instantiate new Gateway Response
	 * 
	 * @param boolean $success was successful?
	 * @param string $message message
	 * @param array $params parameters
	 * @param array $options options
	 */
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

	/**
	 * Needs fraud review?
	 * 
	 * @return booelan 
	 */
	public function needsFraudReview()
	{
		return $this->_fraud_review;
	}

	/**
	 * Was success?
	 * 
	 * @return boolean 
	 */
	public function success()
	{
		return $this->wasSuccessfull();
	}

	/**
	 * Was success?
	 * 
	 * @return boolean 
	 */
	public function wasSuccessfull()
	{
		return $this->_success;
	}

	/**
	 * Was test??
	 * 
	 * @return boolean 
	 */
	public function wasTest()
	{
		return $this->_test;
	}

	protected function getParam($key)
	{
		return isset($this->params[$key]) ? $this->params[$key] : null;
	}
}

?>
