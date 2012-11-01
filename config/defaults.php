<?php
$config = \P3\Config\Handler::singleton();


/* The default root directory for action views templates */
$config->action_view->base_path = \P3\ROOT.'/app/views';

/* The default delivery handler for action mailers */
$config->action_mailer->delivery_handler = 'P3\Mail\Message\Delivery\Standard';

/* Class used for reading/writing active records */
$config->active_record->fixture_class = 'P3\ActiveRecord\Fixture\Database';

/* Logging Level - needs to be configured by default in environments/* */
$config->logging->log_level = P3\System\Logging\Engine::LEVEL_INFO;

/* SMTP settings, or false to fallback to sendmail */
$config->mail->delivery->smtp = false;

/* What format to assume if not supplied in URL */
$config->routing->default_format = 'html';

/* Prevent apps from outputting everything except single controller responses */
$config->trap_extraneous_output = true;


?>