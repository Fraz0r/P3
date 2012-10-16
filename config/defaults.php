<?php
$config = \P3\Config\Handler::singleton();


/* The default root directory for action views templates */
$config->action_view->base_path = \P3\ROOT.'/app/views';

/* Class used for reading/writing active records */
$config->active_record->fixture_class = 'P3\ActiveRecord\Fixture\Database';

/* What format to assume if not supplied in URL */
$config->routing->default_format = 'html';

/* Prevent apps from outputting everything except single controller responses */
$config->trap_extraneous_output = true;


?>