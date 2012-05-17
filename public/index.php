<?php ini_set('display_errors', 'true'); ?>
<?php 
	/** TODO: This needs to be fixed here and in phake init process. 
	 *  P3 needs to be able to load from vendor or global include in future
	 */
	define("P3\ROOT", dirname(dirname(__FILE__)));
	define("P3\PATH", P3\ROOT.'/vendor/p3');
?>
<?php require(dirname(dirname(__FILE__)).'/config/environment.php'); ?>