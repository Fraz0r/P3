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
class PayPal extends PayPal\Base
{
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
}

?>
