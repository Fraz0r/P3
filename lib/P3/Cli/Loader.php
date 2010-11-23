<?php
require_once("P3/Loader.php");

class P3_Cli_Loader extends P3_Loader
{
	public static function dispatch(P3_Uri $uri = null)
	{
		parent::dispatch($uri);
	}
}

