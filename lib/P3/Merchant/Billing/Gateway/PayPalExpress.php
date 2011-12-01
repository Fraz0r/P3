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

	public function setup_purchase($money, array $options = array())
	{
		$this->_requires($options, array('return_url', 'cancel_return_url'));

		return $this->_commit('SetExpressCheckout', $this->_build_setup_request('Sale', $money, $options));
	}

//- Private
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

				$xml->tag('n2:SetExpressCheckoutRequestDetails', function(&$xml)use($share){
					$xml->tag('n2:PaymentAction', $share['action']);
					$xml->tag('n2:OrderTotal', $share['obj']->amount($share['money']), array('currencyID' => $share['options']['currency']));

					if(isset($options['max_amount']))
						$xml->tag('n2:MaxAmount', $share['obj']->amount($share['options']['max_amount']), array('currencyID' => $share['options']['currency']));
				});

				if(isset($options['address']))
					$share['obj']->addAddress($xml, 'n2:Address', $options['address']);

				$xml->tag('n2:AddressOverride', isset($options['address_override']) && $options['address_override'] ? '1' : '0');
				$xml->tag('n2:NoShipping', isset($options['no_shipping']) && $options['no_shipping'] ? '1' : '0');
				$xml->tag('n2:ReturnURL', $options['return_url']);
				$xml->tag('n2:CancelURL', $options['cancel_return_url']);
				$xml->tag('n2:IPAddress', $options['ip']);
				$xml->tag('n2:OrderDescription', $options['description']);

				if(isset($options['email']))
					$xml->tag('n2:BuyerEmail', $options['email']);

				$xml->tag('n2:InvoiceID', $options['order_id']);

				# Customization of the payment page
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

				if(isset($options['locale']))
					$xml->tag('n2:LocaleCode', $options['locale']);
			});
		});

		return $xml->contents();
	}
      
	protected function _buildResponse($success, $message, $response, array $options = array()) 
	{
		return new Paypal\ExpressResponse($success, $message, $response, $options);
	}

}

?>