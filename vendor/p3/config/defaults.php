<?php
$config = \P3\Config\Handler::singleton();


/* Prevent apps from outputting everything except single controller responses */
$config->trap_extraneous_output = true;

/* What format to assume if not supplied in URL */
$config->routing->default_format = 'html';


?>