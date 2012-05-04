<?php

namespace P3\Merchant\Billing;

/**
 * Description of CreditCard
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Merchant\Billing
 * @version $Id$
 */
class CreditCard extends \P3\Model\Base
{
	/**
	 *         first_name:  Card Holder's First Name
	 *          last_name:  Card Holder's First Name
	 *             number:  Credit Card Number
	 *              month:  Expiration Month 
	 *               year:  Expiration Year [YYYY]
	 * verification_value:  CVV
	 */

	public $type = 'credit_card';
	private $_card_type;

	public static $_validatesPresence = array(
		'number',
		'first_name',
		'last_name',
		'month',
		'year',
		'verification_value',
	);

	/**
	 * Credit Card Company Detection Map
	 * @var array
	 */
	public static $CARD_COMPANIES = array(
		'visa'               => '/^4\d{12}(\d{3})?$/',
		'master'             => '/^(5[1-5]\d{4}|677189)\d{10}$/',
		'discover'           => '/^(6011|65\d{2})\d{12}$/',
		'american_express'   => '/^3[47]\d{13}$/',
		'diners_club'        => '/^3(0[0-5]|[68]\d)\d{11}$/',
		'jcb'                => '/^3528\d{12}$/',
		'switch'             => '/^6759\d{12}(\d{2,3})?$/',
		'solo'               => '/^6767\d{12}(\d{2,3})?$/',
		'dankort'            => '/^5019\d{12}$/',
		'maestro'            => '/^(5[06-8]|6\d)\d{10,17}$/',
		'forbrugsforeningen' => '/^600722\d{10}$/',
		'laser'              => '/^(6304[89]\d{11}(\d{2,3})?|670695\d{13})$/'
	);

	public function valid()
	{
		if(!parent::valid())
			return false;

		if(strtotime(date('Y-m-01')) 
				>= strtotime(implode('-', array($this->year, sprintf('%02d', $this->month + 1), '01'))))
			$this->_addError('expiration', 'This card is expired');

		return !count($this->_errors);
	}


	public function __get($name)
	{
		switch($name) {
			case 'card_type':
				if(is_null($this->_card_type)) {
					if(empty($this->number))
						return false;

					$match = false;
					foreach(self::$CARD_COMPANIES as $n => $p) {
						if(preg_match($p, $this->number)) {
							$match = $n;
							break;
						}
					}

					if($match)
						$this->_card_type = $match;
				}

				return $this->_card_type;
				break;
			default:
				return parent::__get($name);
		}
	}
}

?>