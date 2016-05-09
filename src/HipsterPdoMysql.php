<?php

namespace org\hrg\php_hipster_sql{

	class HipsterPdoMysql extends HipsterPdo{
		var $db_type = 'mysql';
		
		function SqlPdoMysql(){
			$this->db_type = 'mysql';
			$this->columQuote1 = $this->columQuote2 = '`';
		}

	}

}