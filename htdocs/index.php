<?php
/**
 *   This file is responsible for Booting your application.  Little to no modification
 * should be needed
 */

require(realpath(dirname(__FILE__).'/..').'/lib/P3/P3.php');

if(P3::development()) {
	ini_set("display_errors", "true");
	error_reporting(E_ALL);
}

try {
	P3::boot();
} catch(P3\Exception\Base $e) {
	$view = new P3\Template\Base();
	$view->exception = $e;

	switch ((int)$e->getCode()) {
		case 404:
			header("HTTP/1.0 404 Not Found");
			$view->heading = "We didn't find what you were looking for.";
			$view->body    = "It's possible the page has moved.";
			$view->display('site/error.tpl');
			break;
		case 500:
		default:
			$view->heading = "Something went wrong";
			$view->body    = (P3::production()) ? "We were notified about this issue, and will get it resolved." : $e->getMessage();
			$view->display('site/error.tpl');
	}
}

?>
