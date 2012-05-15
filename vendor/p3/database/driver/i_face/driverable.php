<?php

namespace P3\Database\IFace;

/**
 * Description of driver
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
interface Driverable
{
	public function get_table_info($table);
	public function get_query_class();
}

?>
