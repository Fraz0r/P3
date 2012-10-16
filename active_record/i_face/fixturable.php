<?php

namespace P3\ActiveRecord\IFace;

/**
 *
 * @author Tim Frazier <tim.frazier at gmail.com>
 */
interface Fixturable
{
	public function write(array $data, $table, $pk = 'id');
}

?>