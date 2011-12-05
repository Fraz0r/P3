<?php

namespace P3\Merchant\Billing\Gateway\PayPal;
use       P3\Merchant\Billing\Response;

/**
 * Description of Base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
abstract class ExpressBase extends Base
{
	protected static $_live_redirect_url = 'https://www.paypal.com/cgibin/webscr?cmd=_express-checkout&token=';

//- Public	
	public function redirect_url()
	{
		return $this->inTestMode() ? static::$_test_redirect_url : static::$_live_redirect_url;
	}

	public function redirect_url_for($token, array $options = array())
	{
		$options = array_merge(array('review' => true), $options);

		return $this->redirect_url().($options['review'] ? $token : $token.'&useraction=commit');
	}
}

?>