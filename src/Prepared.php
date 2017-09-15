<?php

namespace org\hrg\php_hipster_sql{

	class Prepared{
		var $sql = "";
		var $args = array();

		function __construct($sql=null){
			$this->append(func_num_args() == 1 && is_array($sql) ? $sql:func_get_args());
		}

		function append($sql=null){
			$arr = (func_num_args() == 1 && is_array($sql)) ? $sql : func_get_args();

			$this->append_array($arr[0],array_slice($arr, 1));
		}
		
		function append_array($sql, $args){
			
			$this->sql .= $sql;
			
			$count = count($args);
			$i=0; 

			while($i<$count){
				$this->args[] = $args[$i];
				$i++;
			}
		}

		function get_sql(){
			return $this->sql;
		}

		function get_args(){
			return $this->args;
		}
	}
}
