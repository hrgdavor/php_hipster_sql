<?php

namespace org\hrg\php_hipster_sql{

	class HipsterPdoMysql extends HipsterPdo{
		var $db_type = 'mysql';
		
		function SqlPdoMysql(){
			$this->db_type = 'mysql';
		}

		/* Convert to valid column name and escape if needed */
		function q_column($str){
			return '`'.$this->escape($str).'`';
		}

		/* Convert to valid column name */
		function q_table($str){
			return '`'.$this->escape($str).'`';
		}

	}

}