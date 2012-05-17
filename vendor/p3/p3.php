<?php

//define("P3\PATH", dirname(__FILE__));

final class P3
{
	private static $_request;
	private static $_root_path = '/';
	private static $_router;


//- Public Static
	public static function boot()
	{
		if(self::config()->trap_extraneous_output)
			ob_start();

		try {
			self::load_routes();
			self::router()->dispatch();
			
			if(self::config()->trap_extraneous_output)
				ob_end_clean();
		} catch(Exception $e) {
			self::_handle_exception($e);
		}
	}

	public static function config()
	{
		return P3\Config\Handler::singleton();
	}

	public static function development()
	{
		return P3\Initializer::development();
	}

	public static function env($env = null)
	{
		return P3\Initializer::env($env);
	}

	public static function load_routes()
	{
		if(!is_readable(P3\ROOT.'/config/routes.php'))
			throw new P3\Exception\FileNotFound('config/routes.php');

		require(P3\ROOT.'/config/routes.php');
	}

	public static function production()
	{
		P3\Initializer::production();
	}


	public static function request()
	{
		if(!isset(self::$_request))
			self::$_request = new P3\Net\Http\Request;

		return self::$_request;
	}

	public static function root_path($path = null)
	{
		if(is_null($path)) {
			return self::$_root_path;
		} else {
			self::$_root_path = $path;
		}
	}

	public static function router()
	{
		if(!isset(self::$_router))
			self::$_router = new P3\Router;

		return self::$_router;
	}

//- Private Static
	private static function _handle_exception(Exception $e)
	{
		if(self::config()->trap_extraneous_output)
			ob_end_clean();

		if(self::production()) {
			$code = $e->getCode();

			$file = ($code == 404 ? '404' : '500').'.html';

			$file = P3\ROOT.'/public/'.$file;

			P3\Net\Http\Response::process(new \P3\Net\Http\Response(file_get_contents($file), array(), $code));
		} else {
			if(isset($e->xdebug_message))
				echo '<table>'.$e->xdebug_message.'</table>';
			else
				var_dump($e);
		}
	}
}

?>