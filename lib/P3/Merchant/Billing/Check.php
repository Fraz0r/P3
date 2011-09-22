<?php

namespace P3\Merchant\Billing;

/**
 * Description of Check
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 * @package P3\Merchant\Billing
 * @version $Id$
 */
class Check extends \P3\Model\Base
{
	  /**
	   * first_name
	   * last_name 
	   * routing_number
	   * account_number
	   * account_holder_type (business|personal)
	   * account_type (checking|savings)
	   * number
	   */

	public $type = 'check';
}

?>