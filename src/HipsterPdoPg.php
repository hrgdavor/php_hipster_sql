<?php

namespace org\hrg\php_hipster_sql{

	use PDO;

	class HipsterPdoPg extends HipsterPdo{

		function __construct(){
			$this->db_type = 'pgsql';
		}

		/** For "RETURNING primColumn" to work, you must implement a method that will know your convention
		    and give back the name of pk column column for a specific table. Some may use id for all tables or
		    concat tableName with '_id'. You can even account for odd cases that are not followind the convention */
		// abstract protected function getPrimary($tableName);

		/** We implement this method by adding "RETURNING primColumn" to the INSERT statement.*/
		function insert($sql){
			return $this->one( func_num_args() > 1 ? func_get_args():$sql );
		}

		function build_insert($tableName, $values){
			$q = parent::build_insert($tableName, $values);
			$primColumn = $this->getPrimary($tableName);

			if($primColumn)// some table may not have a primary column
				$q[count($q)-1] .= " RETURNING ".$primColumn;
		}
	}

} 