<?php

namespace P3\Merchant\Billing\Gateway;
use       P3\Merchant\Billing\Response;
use       P3\XML\Builder as XMLBuilder;


/**
 * PayPal Gateway wrapper
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Merchant\Billing\Gateway
 * @version $Id$
 */
class PayPalExpress extends PayPal\ExpressBase
{
	protected static $_test_redirect_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';

	public function details_for($token)
	{
		return $this->_commit('GetExpressCheckoutDetails', $this->_build_get_details_request($token));
	}

	public function purchase($money, array $options = array())
	{
		$this->_requires($options, array('token', 'payer_id'));

		return $this->_commit('DoExpressCheckoutPayment', $this->_build_sale_or_authorization_request('Sale', $money, $options));
	}

	public function setup_purchase($money, array $options = array())
	{
		$this->_requires($options, array('return_url', 'cancel_return_url'));

		return $this->_commit('SetExpressCheckout', $this->_build_setup_request('Sale', $money, $options));
	}

//- Private
	private function _build_get_details_request($token)
	{
		$xml = new XMLBuilder(array('indent' => 2));

		$share = array(
			'class' => get_class($this),
			'token' => $token
		);

		$xml->tag('GetExpressCheckoutDetailsReq', array('xmlns' => self::PAYPAL_NAMESPACE), function(&$xml)use($share) {
			$xml->tag('GetExpressCheckoutDetailsRequest', array('xmlns:n2' => $share['class']::EBAY_NAMESPACE), function(&$xml)use($share) {
				$xml->tag('n2:Version', $share['class']::API_VERSION);
				$xml->tag('Token', $share['token']);
			});
		});

		return $xml->contents();
	}

	private function _build_sale_or_authorization_request($action, $money, array $options)
	{
		if(!isset($options['notify_url']))
			$options['notify_url'] = '';

		$share = array(
			'class'  => get_class($this),
			'obj'    => $this,
			'action' => $action,
			'money'  => $money,
			'options' => $options,
			'currency' => isset($options['currency']) ? $options['currency'] : $this->currency()
		);

		$xml = new XMLBuilder(array('indent' => 2));

		$xml->tag('DoExpressCheckoutPaymentReq', array('xmlns' => static::PAYPAL_NAMESPACE), function(&$xml)use($share) {
			$xml->tag('DoExpressCheckoutPaymentRequest', array('xmlns:n2' => $share['class']::EBAY_NAMESPACE), function(&$xml)use($share) {
				$xml->tag('n2:Version', $share['class']::API_VERSION);

				$xml->tag('n2:DoExpressCheckoutPaymentRequestDetails', function(&$xml)use($share) {
					$xml->tag('n2:PaymentAction', $share['action']);
					$xml->tag('n2:Token', $share['options']['token']);
					$xml->tag('n2:PayerID', $share['options']['payer_id']);

					$xml->tag('n2:PaymentDetails', function(&$xml)use($share) {
						$xml->tag('n2:OrderTotal', $share['obj']->amount($share['money']), array('currencyID' => $share['currency']));

						// All of the values must be included together and add up to the order total
						if(isset($share['options']['subtotal'])
							&& isset($share['options']['shipping'])
							&& isset($share['options']['handling'])
							&& isset($share['options']['tax'])) {

							$xml->tag('n2:ItemTotal', $share['obj']->amount($share['options']['subtotal']), array('currencyID' => $share['currency']));
							$xml->tag('n2:ShippingTotal', $share['obj']->amount($share['options']['shipping']), array('currencyID' => $share['currency']));
							$xml->tag('n2:HandlingTotal', $share['obj']->amount($share['options']['handling']), array('currencyID' => $share['currency']));
							$xml->tag('n2:TaxTotal', $share['obj']->amount($share['options']['tax']), array('currencyID' => $share['currency']));
						}

						$xml->tag('n2:NotifyURL', $share['options']['notify_url']);
					});
				});
			});
		});

		return $xml->contents();
	}

	private function _build_setup_request($action, $money, $options)
	{
		if(!isset($options['currency']))
			$options['currency'] = $this->currency($money);

		if(!isset($options['description']))
			$options['description'] = '';

		if(!isset($options['order_id']))
			$options['order_id'] = mt_rand();

		$share = array(
			'obj'     => $this,
			'class'   => get_class($this),
			'action'  => $action,
			'money'   => $money,
			'options' => $options
		);

		$xml = new XMLBuilder(array('indent' => 2));
		$xml->tag('SetExpressCheckoutReq', array('xmlns' => self::PAYPAL_NAMESPACE), function(&$xml)use($share){
			$xml->tag('SetExpressCheckoutRequest', array('xmlns:n2' => $share['class']::EBAY_NAMESPACE), function(&$xml)use($share){
				$options = $share['options'];

				$xml->tag('n2:Version', $share['class']::API_VERSION);

				$xml->tag('n2:SetExpressCheckoutRequestDetails', function(&$xml)use($share, $options){
					$xml->tag('n2:ReturnURL', $options['return_url']);
					$xml->tag('n2:CancelURL', $options['cancel_return_url']);
					$xml->tag('n2:PaymentAction', $share['action']);
					$xml->tag('n2:OrderTotal', $share['obj']->amount($share['money']), array('currencyID' => $share['options']['currency']));

					if(isset($options['max_amount']))
						$xml->tag('n2:MaxAmount', $share['obj']->amount($share['options']['max_amount']), array('currencyID' => $share['options']['currency']));

					if(isset($options['address']))
						$share['obj']->addAddress($xml, 'n2:Address', $options['address']);

					$xml->tag('n2:AddressOverride', isset($options['address_override']) && $options['address_override'] ? '1' : '0');

					if(isset($options['locale']))
						$xml->tag('n2:LocaleCode', $options['locale']);
					
					$xml->tag('n2:NoShipping', isset($options['no_shipping']) && $options['no_shipping'] ? '1' : '0');
					$xml->tag('n2:IPAddress', $options['ip']);
					$xml->tag('n2:OrderDescription', $options['description']);

					if(isset($options['email']))
						$xml->tag('n2:BuyerEmail', $options['email']);

					$xml->tag('n2:InvoiceID', $options['order_id']);

					// Customization of the payment page
					if(isset($options['page_style']))
						$xml->tag('n2:PageStyle', $options['page_style']);

					if(isset($options['header_image']))
						$xml->tag('n2:cpp-image-header', $options['header_image']);

					if(isset($options['header_background_color']))
						$xml->tag('n2:cpp-header-back-color', $options['header_background_color']);

					if(isset($options['header_border_color']))
						$xml->tag('n2:cpp-header-border-color', $options['header_border_color']);

					if(isset($options['background_color']))
						$xml->tag('n2:cpp-payflow-color', $options['background_color']);

					if(isset($options['allow_guest_checkout']) && $options['allow_guest_checkout']) {
						$xml->tag('n2:SolutionType', 'Sole');
						$xml->tag('n2:LandingPage', 'Billing');
					}
				});
			});
		});

		return $xml->contents();
	}
      
	protected function _buildResponse($success, $message, $response, array $options = array()) 
	{
		return new PayPal\ExpressResponse($success, $message, $response, $options);
	}

}

?>