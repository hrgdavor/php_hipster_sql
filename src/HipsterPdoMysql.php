<?php

namespace org\hrg\php_hipster_sql{

	class HipsterPdoMysql extends HipsterPdo{
		
		function __construct(){
			$this->db_type = 'mysql';
			$this->columQuote1 = $this->columQuote2 = '`';
		}

	}

}