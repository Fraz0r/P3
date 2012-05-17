<?php
require(P3\PATH.'/'.'initializer.php');
/* Configs added to this file will be global across all environments */

P3\Initializer::run(function($config) {
	$config->trap_extraneous_output = false;
});

/* Dont edit past this line */
require(dirname(__FILE__).'/boot.php');
?>