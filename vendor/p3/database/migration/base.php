<?php

namespace P3\Database\Migration;

require_once(P3\ROOT.'/database/migration/helper.php');

/**
 * Description of base
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
class Base
{
	abstract function up() {}
	abstract function down() {}
}

?>