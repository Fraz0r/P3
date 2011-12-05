<?php

namespace P3\Merchant\Billing\Gateway\PayPal;
use       P3\Merchant\Billing\Response;

/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class ExpressResponse extends Response
{
//- Public
	public function email()
	{
		return $this->getParam('payer');
	}

	public function name()
	{
		$parts = array(
			$this->getParam('first_name'),
			$this->getParam('middle_name'),
			$this->getParam('last_name')
		);

		return implode(' ', array_filter($parts));
	}

	public function token()
	{
		return $this->getParam('token');
	}

	public function payer_id()
	{
		return $this->getParam('payer_id');
	}

	public function payer_country()
	{
		return $this->getParam('payer_country');
	}

	public function address()
	{
		return array(
			'name'       => $this->getParam('name'),
			'company'    => $this->getParam('payer_business'),
			'address1'   => $this->getParam('street1'),
			'address2'   => $this->getParam('street2'),
			'city'       => $this->getParam('city_name'),
			'state'      => $this->getParam('state_or_province'),
			'country'    => $this->getParam('country'),
			'zip'        => $this->getParam('postal_code'),
			'phone'      => null
		);
	}
}

?>