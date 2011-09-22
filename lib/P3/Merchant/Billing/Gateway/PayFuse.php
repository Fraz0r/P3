<?php

namespace P3\Merchant\Billing\Gateway;
use       P3\Merchant\Billing\Response;
use       P3\XML\Builder as XMLBuilder;

/**
 * PayFuse payment processor
 * 
 * NOTE:  Currently only purchase() (Credit || ACH) is implemented. 
 *        PayFuse does support a slew of other features: void, pre/post auth, settle, etc
 *        (Please contribute if you need any of these)
 * 
 * Example:
 * 		$gateway = new P3\Merchant\Billing\Gateway\PayFuse(array(
 * 			'login'    => 'USER_ID',   // These are provided to you buy PayFuse
 * 			'password' => 'PASSWORD',
 * 			'alias'    => 'ALIAS'
 * 		));
 * 
 * 		$gateway->purchase(10.23, new P3\Merchant\Billing\CreditCard($_POST['credit_card'));
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Merchant\Billing\Gateway
 * @since v1.1.6
 * @version $Id$
 */
class PayFuse extends Base
{
//- Constants
	const ACCOUNT_TYPE_SAVINGS  = 0;
	const ACCOUNT_TYPE_CHECKING = 1;

	const CHECK_ENTRY_CLASS_ARC = 'ARC'; // Convert paper check received via mail or drop box. Currently NOT supported
	const CHECK_ENTRY_CLASS_CCD = 'CCD'; // (Cash Concentration or Disbursement) For business-to-business orders.
	const CHECK_ENTRY_CLASS_PPD = 'PPD'; // (Prearranged Payment and Deposit) For business-to-consumer orders. This is the default value.
	const CHECK_ENTRY_CLASS_WEB = 'WEB'; // Internet-initiated entry function. Internet authorization to debit consumer account.
	const CHECK_ENTRY_CLASS_TEL = 'TEL'; // Telephone initiated entry function. Telephone authorization for a one time debit of the consumer account.

	const CHECK_TYPE_COMPANY  = 0;
	const CHECK_TYPE_PERSONAL = 1;

	const COMMAND_AUTH     = 2;
	const COMMAND_PRE_AUTH = 3;
	const COMMAND_VOID     = 4;
	const COMMAND_CREDIT   = 5;

	const CVV_CARDHOLDER_PRESENT                                     =  1;
	const CVV_CARDHOLDER_PRESENT_SIGNATURE_OBTAINED                  =  2;
	const CVV_CARDHOLDER_NOT_PRESENT_UNKNOWN                         =  3;
	const CVV_CARDHOLDER_NOT_PRESENT_MAIL_ORDER                      =  4;
	const CVV_CARDHOLDER_NOT_PRESENT_TELEPHONE                       =  5;
	const CVV_CARDHOLDER_NOT_PRESENT_STANDING_AUTHORIZATION          =  6;
	const CVV_CARDHOLDER_NOT_PRESENT_ELECTRONIC_TRANSACTION          =  7;
	const CVV_CARDHOLDER_NOT_PRESENT_RECURRING_BILLING               =  8;
	const CVV_ADDRESS_USED_FOR_ID                                    =  9;
	const CVV_CARD_NOT_PRESENT                                       = 10;
	const CVV_CARD_NOT_PRESENT_SET_WITH_MERCHANT_AND_CERT            = 11;
	const CVV_CARD_NOT_PRESENT_CERT_ONLY                             = 12;
	const CVV_CARD_NOT_PRESENT_BUT_PAYER_AUTHENTICATION_WAS_USED     = 13;
	const CVV_CARDHOLDER_PRESENT_BUT_CARD_INFORMATION_MANUALLY_KEYED = 14;

	const COUNTRY_CODE_US = 840;

	const DOC_VERSION      = '1.0';
	const DOC_CONTENT_TYPE = 'OrderFormDoc';

	const FRAUD_RESULT_NONE              = 0;
	const FRAUD_RESULT_ACCEPTED          = 1;
	const FRAUD_RESULT_CUSTOMER_NOTIFIED = 2;
	const FRAUD_RESULT_REJECTED          = 3;
	const FRAUD_RESULT_REVIEW            = 4;
	const FRAUD_RESULT_NO_ACTION         = 5;

	const MODE_PRODUCTION    = 'P';
	const MODE_TEST_APPROVAL = 'Y';
	const MODE_TEST_DECLONE  = 'N';
	const MODE_TEST_RANDOM   = 'R';

	const PIPELINE_PAYMENT          = 'Payment';
	const PIPELINE_PAYMENT_NO_FRAUD = 'PaymentNoFraud';
	const PIPELINE_PAYMENT_FA       = 'PaymentFA';
	const PIPELINE_SETTLEMENT       = 'Settlement';
	const PIPELINE_SHIPPING         = 'Shipping';

	const SANDBOX_ALIAS = '136';
	const SANDBOX_USER  = 'XUTA';
	const SANDBOX_PASS  = 'Xuta1999';
	const SANDBOX_PORT  = 11500;

	const TRANSACTION_STATUS_APPROVED = 'A';
	const TRANSACTION_STATUS_ERROR    = 'E';

	protected $_money_format = 'cents';

	private $_mode     = null;
	private $_pipeline = self::PIPELINE_PAYMENT;
	private $_cardholder_present_code = self::CVV_CARDHOLDER_NOT_PRESENT_ELECTRONIC_TRANSACTION;
	private $_check_entry_class = self::CHECK_ENTRY_CLASS_WEB;
	private $_use_sandbox = false;

	private static $_IGNORE_IN_RESPONSE = array(
		'ContentType',
		'Instructions',
		'OrderFormDoc',
		'Name',
		'Password',
		'Alias',
		'EffectiveAlias',
		'ClientId',
		'EffectiveClientId'
	);

	/**
	 * URL Enpoints
	 * 
	 * @var array
	 */
	public static $HOSTS = array(
		'sandbox' => 'https://test5x.clearcommerce.com',
		'live'    => 'https://xmlic.payfuse.com'
	);

//- Public
	/**
	 * construct.  Requires login, alias, and passord to be passed in $options
	 * 
	 * @param array $options  array of options
	 */
	public function __construct(array $options = array())
	{
		$this->_requires($options, array('login', 'alias', 'password'));

		if(!isset($options['port']))
			$options['port'] = 443;

		if(isset($options['mode']))
			$this->mode($options['mode']);
		else
			$this->_chooseBestMode();


		parent::__construct($options);
	}

	/**
	 * Get/Set _check_entry_class
	 * 
	 * @param null,string $class class to set - class is returned if not sent
	 * @return void
	 */
	public function checkEntryClass($class = null)
	{
		if(is_null($class)) {
			return $this->_check_entry_class;
		} else {
			$this->_check_entry_class = $class;
		}
	}
	
	/**
	 * Returns the URL to use for any given transaction 
	 * 
	 * @return string
	 */
	public function endpointURL()
	{
		return $this->sandbox() ? self::$HOSTS['sandbox'] : self::$HOSTS['live'];
	}

	/**
	 * Returns customer name as single string
	 * 
	 * @return string name of customer 
	 */
	public function getName()
	{
		return implode(' ', array($this->_options['first_name'], $this->_options['last_name']));
	}

	/**
	 * Determines what transaction "type" to use based on command passed 
	 * 
	 * @param int $command command being processed
	 * @param varies $payment_method CreditCard or Check (objects) to process
	 * 
	 * @return string transaction type 
	 * @throws \P3\Merchant\Exception\ArgumentError
	 */
	public function getTypeForCommandAndPayment($command, $payment_method = null)
	{
		switch($command)
		{
			case self::COMMAND_AUTH:
				if(is_a($payment_method, 'P3\Merchant\Billing\CreditCard')) 
					return 'Credit';
				elseif(is_a($payment_method, 'P3\Merchant\Billing\Check')) 
					return 'Auth';
				else throw new \P3\Merchant\Exception\ArgumentError('Unsupported payment method');
				break;
		}
	}

	/**
	 * Get/Set CVV2 present code
	 * 
	 * @param int $code Card Holder Present Code
	 * @return void,int 
	 */
	public function cardholderPresentCode($code = null)
	{
		if(is_null($code)) {
			return $this->_cardholder_present_code;
		} else {
			$this->_cardholder_present_code = $code;
		}
	}

	/**
	 * Determines if the gateway is in "test mode"
	 * 
	 * @return boolean true if test, false if production 
	 */
	public function inTestMode()
	{
		return 
			parent::inTestMode() || 
			$this->sandbox()     || 
			(!is_null($this->_mode) && in_array($this->mode(), array(
				self::MODE_TEST_APPROVAL, 
				self::MODE_TEST_DECLONE, 
				self::MODE_TEST_RANDOM
				)));
	}
	
	/**
	 * Get/Set mode 
	 * 
	 * @param string $mode Mode for transactions to set
	 * @return string,void 
	 * 
	 * @see _chooseBestMode
	 */
	public function mode($mode = null)
	{
		if(is_null($mode)) {
			if(is_null($this->_mode))
				$this->_chooseBestMode();

			return $this->_mode;
		} else {
			$this->_mode = $mode;
		}
	}

	/**
	 * Get/Set pipeline for transaction
	 * 
	 * @param string $pipeline pipline to set
	 * @return string,void 
	 */
	public function pipeline($pipeline = null)
	{
		if(is_null($pipeline)) {
			return $this->_pipeline;
		} else {
			$this->_pipeline = $pipeline;
		}
	}

	/**
	 * Process a transaction
	 * 
	 * @param float,int $money Amount to charge (IN DOLLARS...  1.13 = $1.13)(NOT 113)
	 * @param varies $payment_method CreditCard or Check to process
	 * @param array $options array of options 
	 * 
	 * @return \P3\Merchant\Billing\Response
	 * 
	 * @throws \P3\Merchant\Exception\ArgumentError
	 * @throws \P3\Merchant\Exception\MalformedRequest
	 */
	public function purchase($money, $payment_method, array $options = array())
	{
		if(isset($options['billing_address'])) {
			$options['address'] = $options['billing_address'];
			unset($options['billing_address']);
		}

		$this->_requires($options, array('address'));

		if(is_a($options['address'], 'P3\Merchant\Billing\Address'))
			$options['address'] = $options['address']->export();
		elseif(!is_array($options['address']))
			throw new \P3\Merchant\Exception\ArgumentError('Unknown address type passed.', array(), 500);

		$this->_options['first_name'] = $payment_method->first_name;
		$this->_options['last_name']  = $payment_method->last_name;

		return $this->_commit($this->_buildTransactionRequest(self::COMMAND_AUTH, $money, $payment_method, $options));
	}

	/**
	 * Get/Set sandbox mode
	 * 
	 * @param boolean $bool true puts gateway in sandbox, false to production server
	 * @return boolean,void 
	 */
	public function sandbox($bool = null)
	{
		if(is_null($bool))
			return $this->_use_sandbox;
		else
			$this->_use_sandbox = $bool;
	}

//- XML Builder Methods  (These have to be public because of PHPs lame closure scoping)
	/**
	 * Add Address Block
	 * 
	 * @param XMLBuilder $xml xml builder to add block to (This is passed as a reference)
	 * @param array $share array to pass between nested closures
	 */
	public function xAddAddress($xml, $share)
	{
		$xml->BillTo(function(&$xml)use($share){
			$xml->Location(function(&$xml)use($share){
				$xml->Address(function(&$xml)use($share){
					$address = $share['options']['address'];

					if(strlen($share['this']->getName()))
						$xml->Name($share['this']->getName());

					if(isset($address['address1']))
						$xml->Street1($address['address1']);

					if(isset($address['address2']))
						$xml->Street2($address['address2']);

					if(isset($address['city']))
						$xml->City($address['city']);

					if(isset($address['state']))
						$xml->StateProv($address['state']);

					if(isset($address['zip']))
						$xml->PostalCode($address['zip']);

					$xml->Country((string)constant(__CLASS__.'::COUNTRY_CODE_US'));  // If you need other locales, please feel free to contribute
				});
			});
		});
	}

	/**
	 * Add Consumer Block
	 * 
	 * @param XMLBuilder $xml xml builder to add block to (This is passed as a reference)
	 * @param array $share array to pass between nested closures
	 */
	public function xAddConsumer($xml, $share)
	{
		$xml->Consumer(function(&$xml)use($share){
			if(isset($share['options']['email']))
				$xml->Email($share['options']['email']);

			$share['this']->xAddAddress($xml, $share);

			if(isset($share['payment_method']))
				$share['this']->xAddPaymentMethod($xml, $share);
		});
	}

	/**
	 * Add Check Block
	 * 
	 * @param XMLBuilder $xml xml builder to add block to (This is passed as a reference)
	 * @param array $share array to pass between nested closures
	 */
	public function xAddCheck($xml, $check)
	{
		$obj = $this;
		$xml->Type('Check');
		$xml->Check(function(&$xml)use($check, $obj){
			$xml->AccountNumber((string)$check->account_number);

			switch($check->account_type) {
				case 'savings':
					$account_type = constant(__CLASS__.'::ACCOUNT_TYPE_SAVINGS');
					break;
				case 'checking':
					$account_type = constant(__CLASS__.'::ACCOUNT_TYPE_CHECKING');
					break;
			}

			$xml->AccountType((string)$account_type, array('DataType' => 'S32'));

			$xml->AuthType('0', array('DataType' => 'S32'));

			$xml->CheckNumber($check->number);

			switch($check->account_holder_type) {
				case 'business':
					$check_type = constant(__CLASS__.'::CHECK_TYPE_COMPANY');
					break;
				case 'personal':
					$check_type = constant(__CLASS__.'::CHECK_TYPE_PERSONAL');
					break;
			}

			$xml->CheckType((string)$check_type, array('DataType' => 'S32'));

			$xml->EntryClass($obj->checkEntryClass());

			$xml->RoutingNumber((string)$check->routing_number);
		});
	}

	/**
	 * Add Credentials Block
	 * 
	 * @param XMLBuilder $xml xml builder to add block to (This is passed as a reference)
	 * @param array $share array to pass between nested closures
	 */
	public function xAddCredentials(&$xml)
	{
		$options = $this->_options;

		$xml->User(function(&$xml)use($options){
			$xml->Name($options['login']);
			$xml->Password($options['password']);
			$xml->Alias($options['alias']);
		});
	}

	/**
	 * Add CreditCard Block
	 * 
	 * @param XMLBuilder $xml xml builder to add block to (This is passed as a reference)
	 * @param array $share array to pass between nested closures
	 */
	public function xAddCreditCard($xml, $credit_card)
	{
		$xml->Type('CreditCard');
		$xml->CreditCard(function(&$xml)use($credit_card){
			$xml->Number((string)$credit_card->number);
			$xml->Expires(vsprintf('%02s%02s', array((string)$credit_card->month, substr($credit_card->year, -2))), array('DataType' => 'ExpirationDate', 'Locale' => '840'));

			if(isset($credit_card->verification_value)) {
				$cvv_present = true;
				$xml->Cvv2Val((string)$credit_card->verification_value);
				$xml->Cvv2Indicator('1'); //present and submitted
			} else {
				$xml->Cvv2Indicator('2'); //not present, per custommer
			}
		});
	}

	/**
	 * Add Instructions Block
	 * 
	 * @param XMLBuilder $xml xml builder to add block to (This is passed as a reference)
	 * @param array $share array to pass between nested closures
	 */
	public function xAddInstructions($xml, $share)
	{
		$xml->Instructions(function(&$xml)use($share){
			$xml->Pipeline($share['this']->pipeline());
		});
	}

	/**
	 * Add Payment Method  Block
	 * 
	 * @param XMLBuilder $xml xml builder to add block to (This is passed as a reference)
	 * @param array $share array to pass between nested closures
	 */
	public function xAddPaymentMethod($xml, $share)
	{
		$this->_requires($share, array('payment_method'));

		$xml->PaymentMech(function(&$xml)use($share){
			$type = $share['payment_method']->type;

			if($type == 'credit_card')
				$share['this']->xAddCreditCard($xml, $share['payment_method']);
			elseif($type == 'check')
				$share['this']->xAddCheck($xml, $share['payment_method']);
			else throw new \P3\Merchant\Exception\ArgumentError('Unkown payment method passed.', array(), 500);
		});
	}

//- Private
	/**
	 * Wraps transaction request to complete XML Document for PayFuse
	 * 
	 * @param XMLBuilder $request inner portion of document for request
	 * @return XMLBuilder completed xml document
	 */
	private function _buildRequest($request)
	{
		$xml = new XMLBuilder;
		$share = $this->_buildClosureShare(array(
			'request' => $request
		));

		$xml->EngineDocList(function(&$xml)use($share){
			$xml->DocVersion(constant(__CLASS__.'::DOC_VERSION'));
			$xml->EngineDoc(function(&$xml)use($share){
				$xml->ContentType(constant(__CLASS__.'::DOC_CONTENT_TYPE'));

				$share['this']->xAddCredentials($xml);

				$share['this']->xAddInstructions($xml, $share);

				$xml->text($share['request']->contents());
			});
		});

		return $xml;
	}

	/**
	 * Build and return xml for transaction, based on $command
	 * 
	 * @param int $command command being run
	 * @param float,int $money amount being process
	 * @param varies $payment_method CreditCard or Check to process
	 * @param array $options array of options
	 * 
	 * @return XMLBuilder xml form of transaction for PayFuse
	 */
	private function _buildTransactionRequest($command, $money, $payment_method, array $options = array())
	{
		$xml = new XMLBuilder(array('indent' => 2));
		$share = $this->_buildClosureShare(array(
			'money' => $money,
			'command' => $command,
			'payment_method' => $payment_method,
			'options' => array_merge($this->_options, $options)
		));

		$xml->__call(constant(__CLASS__.'::DOC_CONTENT_TYPE'), array(function(&$xml)use($share){
			if(isset($share['options']['order_id']))
				$xml->Id($share['options']['order_id']);

			$xml->Mode($share['this']->mode());

			if(isset($share['options']['description']))
				$xml->Comments($share['options']['description']);

			$share['this']->xAddConsumer($xml, $share);

			$xml->Transaction(function(&$xml)use($share){
				if($share['payment_method']->type == 'credit_card')
					$xml->CardholderPresentCode((string)$share['this']->cardholderPresentCode(), array('DataType' => 'S32'));

				if(isset($share['options']['invoice_number']))
					$xml->InvNumber($share['options']['invoice_number']);

				if(isset($share['options']['po_number']))
					$xml->PoNumber($share['options']['po_number']);

				$xml->Type($share['this']->getTypeForCommandAndPayment($share['command'], $share['payment_method']));

				$xml->CurrentTotals(function(&$xml)use($share){
					$xml->Totals(function(&$xml)use($share){
						$xml->Total((string)$share['this']->amount($share['money']), array('DataType' => 'Money', 'Currency' => '840'));
					});
				});
			});
		}));

		return $xml;
	}

	/**
	 * Returns mode to use for transaction.  Guesses best values if none was passed to gateway
	 * 
	 * @return char mode for <Mode> tag in transaction 
	 */
	private function _chooseBestMode()
	{
		if(isset($this->_options['mode']))
			return $this->_mode = $this->_options['mode'];

		$this->_mode = !$this->inTestMode() ? self::MODE_PRODUCTION : self::MODE_TEST_APPROVAL;
	}

	/**
	 * Process and send request
	 * 
	 * @param XMLBuilder $request full xml document to process
	 * @return \P3\Merchant\Billing\Response response for request
	 */
	private function _commit($request)
	{
		$response = $this->_parse($this->_send($this->_buildRequest($request)));

		return new Response($this->_wasSuccessfull($response), $this->_extractMessage($response), $response, array(
			'test'          => $this->inTestMode(),
			'authorization' => $this->_extractAuthorization($response),
			'fraud_review'  => $this->_needsFraudReview($response)
		));
	}

	/**
	 * Dont judge me... this is the best solution I can come up with since
	 * PHP places closures in their own scopes *sigh*
	 * 
	 * @param array $include hash to include in returned share
	 * @return array passed hash including a 'this' reference
	 */
	private function _buildClosureShare(array $include = array())
	{
		return array_merge(array(
			'this' => $this
		), $include);
	}

	/**
	 * Determines authorization for transaction.  Also referred to as a 'reference'
	 * or 'transaction id'
	 * 
	 * NOTE:  PayFuse actually lets you set this.  To do so, pass 'order_id' to the transaction
	 *        $options.  But do note if you decide to do this it MUST be unique [obviously]
	 * 
	 * @param array $response hash from raw processing response
	 * @return string transaction authorization ('reference')
	 */
	private function _extractAuthorization(array $response)
	{
		return isset($response['order_id']) ? $response['order_id'] : (isset($response['document_id']) ? $response['document_id'] : null);
	}

	/**
	 * Returns a human interpretable 'message' from gateway parse raw response (hash)
	 * 
	 * @param array $response hash from raw processing response
	 * 
	 * @return string message for transaction 
	 */
	private function _extractMessage(array $response)
	{
		return isset($response['notice']) ? $response['notice'] : $response['cc_return_msg'];
	}

	/**
	 * Determines if transaction was placed under 'fraud review' 
	 * 
	 * @param array $response hash from raw processing response
	 * @return bool true if fraud review, false otherewise 
	 */
	private function _needsFraudReview(array $response)
	{
		return isset($response['fraud_weight']) && $response['fraud_weight'] == self::FRAUD_RESULT_REVIEW;
	}

	/**
	 * Parse xml string returned from PayFuse gateway
	 * 
	 * @param string $http_response raw XML string
	 * @return array hash parsed from string 
	 */
	private function _parse($http_response)
	{
		$errors   = array();
		$response = array();

		if(FALSE == ($xml = @\simplexml_load_string($http_response)))
			throw new \P3\Merchant\Exception\MalformedRequest(implode('.  ', libxml_get_errors()), array(), 500);

		foreach($xml->EngineDoc->children() as $node) {
			switch($node->getName()) {
				case 'MessageList':
					$text = (string)$node->Message->Text;
					if(!empty($text))
						$errors[] = $text;
					break;
				default:
					$this->_parseNode($node, $response);
			}
		}

		if(count($errors) && !isset($response['notice']))
			$response['notice'] = implode('. ', $errors);

		return $response;
	}

	/**
	 * Parse xml node recursively [if need be]
	 * 
	 * @param SimpleXMLElement $node node to parse
	 * @param array $response reference of hash being built by _parse
	 * 
	 * @return void
	 * 
	 * @see _parse
	 */
	private function _parseNode($node, &$response)
	{
		if(0 < ($children = $node->children()))
			foreach($children as $child)
				$this->_parseNode($child, $response);
		else
			if(!in_array($node->getName(), self::$_IGNORE_IN_RESPONSE))
				$response[\str::fromCamelCase($node->getName())] = (string)$node;
	}

	/**
	 * Send request document to payfuse and return response
	 * 
	 * @param XMLBuilder $request request document to send
	 * @return string response from PayFuse (xml)
	 * 
	 * @throws \P3\Merchant\Exception\MalformedRequest
	 */
	private function _send($request)
	{
		$ch = curl_init($this->endpointURL());
		curl_setopt($ch, CURLOPT_PORT, $this->_options['port']);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // TODO:  CURLOPT_SSL_VERIFYPEER-0 IS NOT PROPER, FIX [Not Urgent though, for me anyway]
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "CLRCMRC_XML=".urlencode($request->contents()));
		$result = curl_exec($ch);

		//echo '<pre>'.htmlspecialchars($request->contents()).'</pre>';

		if(!$result)
			throw new \P3\Merchant\Exception\MalformedRequest(curl_error($ch), array(), 500);

		return $result;
	}

	/**
	 * Determines if raw response [hash] was a successfull request
	 * 
	 * @param array $response raw response hash from parsing
	 * @return bool  
	 */
	private function _wasSuccessfull(array $response)
	{
		return isset($response['transaction_status']) && $response['transaction_status'] == self::TRANSACTION_STATUS_APPROVED;
	}

	/**
	 * Determines if raw response [hash] was from a transaction in test mode
	 * 
	 * @param array $response raw response hash from parsing
	 * @return bool 
	 */
	private function _wasTest(array $response)
	{
		return $this->sandbox() || in_array($response['mode'], array(self::MODE_TEST_APPROVAL, self::MODE_TEST_DECLONE, self::MODE_TEST_RANDOM));
	}
}

?>
