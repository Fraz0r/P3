<?php

namespace P3\Merchant\Billing;

/**
 * Description of AVSResult
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class AVSResult {
	public static $MESSAGES = array(
		'A' => 'Street address matches, but 5-digit and 9-digit postal code do not match.',
		'B' => 'Street address matches, but postal code not verified.',
		'C' => 'Street address and postal code do not match.',
		'D' => 'Street address and postal code match.',
		'E' => 'AVS data is invalid or AVS is not allowed for this card type.',
		'F' => 'Card member\'s name does not match, but billing postal code matches.',
		'G' => 'Non-U.S. issuing bank does not support AVS.',
		'H' => 'Card member\'s name does not match. Street address and postal code match.',
		'I' => 'Address not verified.',
		'J' => 'Card member\'s name, billing address, and postal code match. Shipping information verified and chargeback protection guaranteed through the Fraud Protection Program.',
		'K' => 'Card member\'s name matches but billing address and billing postal code do not match.',
		'L' => 'Card member\'s name and billing postal code match, but billing address does not match.',
		'M' => 'Street address and postal code match.',
		'N' => 'Street address and postal code do not match.',
		'O' => 'Card member\'s name and billing address match, but billing postal code does not match.',
		'P' => 'Postal code matches, but street address not verified.',
		'Q' => 'Card member\'s name, billing address, and postal code match. Shipping information verified but chargeback protection not guaranteed.',
		'R' => 'System unavailable.',
		'S' => 'U.S.-issuing bank does not support AVS.',
		'T' => 'Card member\'s name does not match, but street address matches.',
		'U' => 'Address information unavailable.',
		'V' => 'Card member\'s name, billing address, and billing postal code match.',
		'W' => 'Street address does not match, but 9-digit postal code matches.',
		'X' => 'Street address and 9-digit postal code match.',
		'Y' => 'Street address and 5-digit postal code match.',
		'Z' => 'Street address does not match, but 5-digit postal code matches.'
	);

	public $code = null;
	public $message = null;
	public $street_match = null;
	public $postal_match = null;

	public function __construct($attrs = null) 
	{
		$attrs = !is_null($attrs) ? $attrs : array();

		if(isset($attrs['code']))
			$this->code = $attrs['code'];

		if(!is_null($this->code))
			$this->message = self::$MESSAGES[$this->code];

		$this->street_match = isset($attrs['street_match']) ? strtoupper($attrs['street_match']) : false;
		$this->postal_match = isset($attrs['postal_match']) ? strtoupper($attrs['postal_match']) : false;
	}

	public function toArray()
	{
		return array(
			'code' => $this->code,
			'message' => $this->message,
			'street_match' => $this->street_match,
			'postal_match' => $this->postal_match
		);
	}
}

?>
