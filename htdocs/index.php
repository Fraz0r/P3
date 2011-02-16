<?php
require(realpath(dirname(__FILE__).'/..').'/lib/P3/Loader.php');
P3\Loader::loadEnv();

if(P3::development()) {
	ini_set("display_errors", "true");
	error_reporting(E_ALL);
}

try {
	P3\Router::dispatch();
} catch(P3\Exception\Base $e) {
	$view = new P3\Template\Base();
	$view->setLayout('application.tpl');
	$view->exception = $e;

	switch ((int)$e->getCode()) {
		case 404:
			header("HTTP/1.0 404 Not Found");
			$view->heading = "We didn't find what you were looking for.";
			$view->body    = "It's possible the page has moved.";
			//$view->display('site/error.tpl');
			var_dump($e);
			break;
		case 500:
		default:
			$view->heading = "Something went wrong";
			$view->body    = (CBG_PRODUCTION) ? "We were notified about this issue, and will get it resolved." : $e->getMessage();
			//$view->display('site/error.tpl');
			var_dump($e);
	}
}

?>
