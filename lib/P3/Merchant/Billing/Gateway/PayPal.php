<?php

namespace P3\Merchant\Billing\Gateway;
use       P3\Merchant\Billing\Response;


/**
 * PayPal Gateway wrapper
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Merchant\Billing\Gateway
 * @version $Id$
 */
class PayPal extends Base 
{
	/**
	 * PayPal API Version
	 */
	const API_VERSION = '72.0';

	/**
	 * PayPal XML Namespace
	 */
	const PAYPAL_NAMESPACE = 'urn:ebay:api:PayPalAPI';

	/**
	 * eBay XML Namespace
	 */
	const EBAY_NAMESPACE   = 'urn:ebay:apis:eBLBaseComponents';

	/**
	 * PayPal Fraud Review Code
	 */
	const FRAUD_REVIEW_CODE = '11610';

	/**
	 * SOAP XML Namespaces
	 * 
	 * @var array
	 */
	public static $ENVELOPE_NAMESPACES = array(
		'xmlns:xsd' => 'http://www.w3.org/2001/XMLSchema',
		'xmlns:env' => 'http://schemas.xmlsoap.org/soap/envelope/',
		'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance'
	);

	/**
	 * XML Namespaces for <Credentials> block
	 * 
	 * @var array
	 */
	public static $CREDENTIALS_NAMESPACES = array( 
		'xmlns'              => self::PAYPAL_NAMESPACE,
		'xmlns:n1'           => self::EBAY_NAMESPACE
	);

	/**
	 * URL Enpoints
	 * 
	 * @var array
	 */
	public static $URLS = array(
		'test' => array(
			'certificate' => 'https://api.sandbox.paypal.com/2.0/',
			'signature'   => 'https://api-3t.sandbox.paypal.com/2.0/'
		),
		'live' => array(
			'certificate' => 'https://api-aa.paypal.com/2.0/',
			'signature'   => 'https://api-3t.paypal.com/2.0/'
		)
	);

	/**
	 * Array of codes to accept as a successful run
	 * 
	 * @var array
	 */
	public static $SUCCESS_CODES = array('Success', 'SuccessWithWarning');

	/**
	 * PayPal signature settable in Bootstrap (can also pass in construct)
	 * 
	 * @var string
	 */
	public static $signature = null;

//- Public
	/**
	 * Instantiate PayPal Gateway
	 * 
	 * @param array $options array of options 
	 */
	public function __construct(array $options = array())
	{
		$this->_requires($options, array('login', 'password'));

		if(!is_null(self::$signature))
			$options['signature'] = self::$signature;

		if(!isset($options['pem']) && !isset($options['signature']))
			throw new \P3\Merchant\Exception\ArgumentError('An API Certificate or API Signature is required to make requests to PayPal', array(), 500);

		parent::__construct($options);
	}

	/**
	 * Adds Address block to Request
	 * 
	 * @param P3\XML\Builder $xml
	 * @param string $element element name
	 * @param array $address adress array
	 * 
	 * @return void
	 */
	public function addAddress(&$xml, $element, $address)
	{
		$xml->tag($element, function(&$xml) use($address){
			$xml->tag('n2:Name', isset($address['name']) ? $address['name'] : '');
			$xml->tag('n2:Street1', isset($address['address1']) ? $address['address1'] : '');
			$xml->tag('n2:Street2', isset($address['address2']) ? $address['address2'] : '');
			$xml->tag('n2:CityName', isset($address['city']) ? $address['city'] : '');
			$xml->tag('n2:StateOrProvince', isset($address['state']) ? $address['state'] : 'N/A');
			$xml->tag('n2:Country', isset($address['country']) ? $address['country'] : '');
			$xml->tag('n2:PostalCode', isset($address['zip']) ? $address['zip'] : '');
			$xml->tag('n2:Phone', isset($address['phone']) ? $address['phone'] : '');
		});
	}

	/**
	 * Adds Credentials block to passed XML Builder
	 * 
	 * @param P3\XML\Builder $xml
	 * @return void
	 */
	public function addCredentials(&$xml)
	{
		$options = $this->_options;

		$xml->tag('RequesterCredentials', self::$CREDENTIALS_NAMESPACES, function(&$xml) use($options){
			$xml->tag('n1:Credentials', array(), function(&$xml) use($options){
				$xml->tag('Username', $options['login']);
				$xml->tag('Password', $options['password']);
				$xml->tag('Subject', isset($options['subject']) ? $options['subject'] : '');

				if(isset($options['signature']))
					$xml->tag('Signature', $options['signature']);
			});
		});
	}

	/**
	 * Adds credit card to passed xml builder
	 * 
	 * @param P3\XML\Builder $xml xml builder
	 * @param P3\Merchant\Billing\CreditCard $credit_card credit card to process
	 * @param array $address address array
	 * @param array $options array of options
	 */
	public function addCreditCard(&$xml, $credit_card, $address, $options)
	{
		$obj = $this;
		$xml->tag('n2:CreditCard', function(&$xml) use($obj, $credit_card, $address, $options){
			$xml->tag('n2:CreditCardType', $obj->creditCardType($credit_card->type));
			$xml->tag('n2:CreditCardNumber', $credit_card->number);
			$xml->tag('n2:ExpMonth', sprintf('%02s', $credit_card->month));
			$xml->tag('n2:ExpYear', sprintf('%04s', $credit_card->year));
			$xml->tag('n2:CVV2', $credit_card->verification_value);

			/* TODO: add switch/solo support */

			$xml->tag('n2:CardOwner', function(&$xml) use($obj, $credit_card, $address, $options){
				$xml->tag('n2:PayerName', function(&$xml) use($obj, $credit_card, $address, $options){
					$xml->tag('n2:FirstName', $credit_card->first_name);
					$xml->tag('n2:LastName', $credit_card->last_name);
				});

				$xml->tag('n2:Payer', isset($options['email']) ? $options['email'] : '');
				
				$obj->addAddress($xml, 'n2:Address', $address);
			});

		});
	}

	/**
	 * Parse credit card type usable for Request
	 * 
	 * @param string $type type to parse
	 * @return string parsed type
	 */
	public function creditCardType($type)
	{
		$type = strtolower($type);

		$mapping = array(
			'visa'             => 'Visa',
			'master'           => 'MasterCard',
			'discover'         => 'Discover',
			'american_express' => 'Amex',
			'switch'           => 'Switch',
			'solo'             => 'Solo'
		);

		return isset($mapping[$type]) ? $mapping[$type] : null;
	}

	/**
	 * Determines and returns usable enpoint for configuration and environement
	 * 
	 * @return type string API Enpoint
	 */
	public function endpointURL()
	{
		return self::$URLS[($this->inTestMode() ? 'test' : 'live')][((isset($this->_options['signature']) && !empty($this->_options['signature'])) ? 'signature' : 'certificate')];
	}

	/**
	 * Determines if we are in test mode
	 * 
	 * @return boolean true if test, false otherwise
	 */
	public function inTestMode()
	{
		return (isset($this->_options['test']) && $this->_options['test']) || $this->gatewayMode() == 'test';
	}

	/**
	 * Authorize and capture payment
	 * 
	 * @param float $money amount to charge
	 * @param P3\Merchant\Billing\CreditCard $credit_card credit card to charge
	 * @param array $options array of options
	 * 
	 * @return P3\Merchant\Billing\Response response from gateway
	 */
	public function purchase($money, $credit_card, array $options = array())
	{
		$this->_requires($options, array('ip'));

		return $this->_commit('DoDirectPayment', $this->_buildSaleOrAuthorizationRequest('Sale', $money, $credit_card, $options));
	}

//- Protected
	/**
	 * Builds and Returns XML Request
	 * 
	 * @param string $body XML Markup for body of the SOAP Envelope
	 * @return string full SOAP request XML
	 */
	protected function _buildRequest($body)
	{
		$xml = new \P3\XML\Builder;
		
		$xml->instruct();

		$obj = $this;

		$xml->tag('env:Envelope', self::$ENVELOPE_NAMESPACES, function(&$xml) use($obj, $body){
			$xml->tag('env:Header', function(&$xml) use($obj){
				$obj->addCredentials($xml);
			});

			$xml->tag('env:Body', function(&$xml) use($body) {
				$xml->text($body);
			});
		});

		return $xml->contents();
	}

	/**
	 * Builds and returns response into P3\Merchant\Gateway\Response
	 * 
	 * @param boolean $success successfullness
	 * @param string $message response message
	 * @param array $response response array
	 * @param array $options array of options
	 * 
	 * @return P3\Merchant\Gateway\Response response from gateway
	 */
	protected function _buildResponse($success, $message, $response, array $options = array())
	{
		return new Response($success, $message, $response, $options);
	}

	/**
	 * Builds and returns XML String for body of SOAP Envelop (For Sale or Authorization)
	 * 
	 * @param string $action action -'sale' or 'authorization'
	 * @param float $money amount to authorize or capture
	 * @param P3\Merchant\Billing\CreditCard $credit_card credit card to charge
	 * @param array $options array of options
	 * 
	 * @return type 
	 */
	protected function _buildSaleOrAuthorizationRequest($action, $money, $credit_card, array $options = array())
	{
		$xml = new \P3\XML\Builder(array('indent' => 2));

		$self = __CLASS__; // lose access to self/this within closures
		$obj = $this;
		
		$xml->tag('DoDirectPaymentReq', array('xmlns' => self::PAYPAL_NAMESPACE), function(&$xml) use($obj, $self, $action, $money, $credit_card, $options){
			$xml->tag('DoDirectPaymentRequest', array('xmlns:n2' => $self::EBAY_NAMESPACE), function(&$xml) use($obj, $self, $action, $money, $credit_card, $options){
				$xml->tag('n2:Version', $self::API_VERSION);
				$xml->tag('n2:DoDirectPaymentRequestDetails', null, function(&$xml) use($obj, $self, $action, $money, $credit_card, $options){
					$xml->tag('n2:PaymentAction', $action);
					$xml->tag('n2:PaymentDetails', null, function(&$xml) use($obj, $self, $action, $money, $credit_card, $options){
						$xml->tag('n2:OrderTotal', $obj->amount($money));

						$xml->tag('n2:NotifyURL', isset($options['notify_url']) ? $options['notify_url'] : '');
						$xml->tag('n2:OrderDescription', isset($options['description']) ? $options['description'] : '');
						$xml->tag('n2:InvoiceID', isset($options['order_id']) ? $options['order_id'] : '');
						
						/* TODO: add shipping address */
					});

					$billing_address = isset($options['billing_address']) ? $options['billing_address'] : isset($options['address']) ? $options['address'] : false;
					if(!$billing_address)
						throw new \P3\Merchant\Exception\ArgumentError("Billing address is required");

					$obj->addCreditCard($xml, $credit_card, $billing_address, $options);
					$xml->tag('n2:IPAddress', $options['ip']);
				});
			});
		});	

		return $xml->contents();
	}

	/**
	 * Runs action on PayPal's api
	 * 
	 * @param string $action action to commit
	 * @param string $request request body for SOAP envelope
	 * 
	 * @return P3\Merchant\Billing\Response response from gateway
	 */
	protected function _commit($action, $request)
	{
        $response = $this->_parse($action, $this->sslPost($this->endpointURL(), $this->_buildRequest($request)));

        return $this->_buildResponse($this->_wasSuccessfull($response), $response['message'], $response, array(
    	    'test' => $this->inTestMode(),
    	    'authorization' => $this->_authorizationFrom($response),
    	    'fraud_review' => $this->_needsFraudReview($response),
    	    'avs_result' => array('code' => isset($response['AVSCode']) ? $response['AVSCode'] : null),
    	    'cvv_result' => isset($response['CVV2Code']) ? $response['CVV2Code'] : null
		));
	}

	/**
	 * Parses XML response from Paypal, and parses/returns into a much more friendly array
	 * 
	 * @param string $action action the request was for
	 * @param string $http_response XML response from association
	 * 
	 * @return array parsed array
	 */
	protected function _parse($action, $http_response)
	{
		$response       = array();
		$error_messages = array();
		$error_codes    = array();
		$xml = new \SimpleXMLElement($http_response->body);

		if(count(($xpath = $xml->xpath("//ns:{$action}Response")))) {
			$root = $xpath[0];

			$short_message = null;
			$long_message  = null;

			foreach($root->children() as $node) {
				switch($node->getName()) {
					case 'Errors':
						foreach($node->children() as $child) {
							switch($child->getName()){
								case 'LongMessage':
									$long_message = (string)$child;
									break;
								case 'ShortMessage':
									$short_message = (string)$child;
									break;
								case 'ErrorCode':
									$error_codes[] = (string)$child;
									break;
							}
						}

						if(!is_null($long_message))
							$message = $long_message;
						elseif(!is_null($short_message))
							$message = $short_message;

						if(isset($message))
							$error_messages[] = $message;
						break;
					default:
						$this->_parseElement($response, $node);
				}
			}

			$response['message'] = implode('. ', $error_messages);
		} elseif(count(($xpath = $xml->xpath('//SOAP-ENV:Fault')))) {
			$this->_parseElement($response, $xpath[0]);
			$response['message'] = sprintf('%s: %s - %s', $response['faultcode'], $response['faultstring'], isset($response['detail']) ? $response['detail'] : '');
		}

		$response['error_codes'] = count($error_codes) ? implode(',', array_unique($error_codes)) : '';
		$response['error_messages'] = implode('. ', $error_messages);

		return $response;
	}

	/**
	 * Parses element and loads into $response (Recursively)
	 * 
	 * @param array $response response array to load into
	 * @param SimpleXMLElement $node XML node to parse
	 */
	protected function _parseElement(&$response,  $node)
	{
		if(count($node->children()))
			foreach($node->children() as $k => $child)
				$this->_parseElement($response, $child);

		else
			$response[$node->getName()] = (string)$node;
	}

	/**
	 * Retrieves authorization code from response
	 * 
	 * @param array $response response array
	 * @return string authorization code 
	 */
	private function _authorizationFrom($response)
	{
		if(isset($response['TransactionID']))
			return $response['TransactionID'];

		elseif(isset($response['AuthorizationID']))
			return $response['AuthorizationID'];

		elseif(isset($response['RefundTransactionID']))
			return $response['RefundTransactionID'];

		return false;
	}

	/**
	 * Returns true if fraud review
	 * 
	 * @param array $response response array
	 * @return boolean true if fraud review 
	 */
	private function _needsFraudReview($response)
	{
		return $response['error_codes'] == self::FRAUD_REVIEW_CODE;
	}

	/**
	 * Determines if the pased response array was successfull
	 * 
	 * @param array $response response array
	 * @return boolean true if successfull, false otherwise 
	 */
	private function _wasSuccessfull($response)
	{
		if(!isset($response['Ack']))
			return false;

		return in_array($response['Ack'], self::$SUCCESS_CODES);
	}
}

?>
