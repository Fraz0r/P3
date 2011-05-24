<?php

namespace P3\Merchant\Billing;

/**
 * Description of CreditCard
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class CreditCard extends \P3\Model\Base
{
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
}

?>