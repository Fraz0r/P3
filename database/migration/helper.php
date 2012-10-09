<?php

namespace
{
	use P3\Database\Migration;

	function add_column($table_name, $column_name, $type, array $options = array()) {
	}

	function create_table($table, $lambda) {
	}

	function drop_table($table) {
	}

	function remove_column($table_name, $column_name) {
	}
}

namespace P3\Database\Migration
{
	class NewTableProxy
	{
		public function __call($method, array $args = array())
		{
		}
	}
}

?>