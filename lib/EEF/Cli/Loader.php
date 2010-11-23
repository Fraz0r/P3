<?php
require_once("EEF/Loader.php");

class EEF_Cli_Loader extends EEF_Loader
{
	public static function dispatch(EEF_Uri $uri = null)
	{
		parent::dispatch($uri);
	}
}

