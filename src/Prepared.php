<?php

namespace org\hrg\php_hipster_sql{

	class Prepared{
		var $sql = "";
		var $args = array();

		function __construct(){
			$this->_append(func_get_args());
		}

		/* Create new instance from array to enable forwarding func_get_args from functions that accept varargs */
		static function from_args($args){
			$p = new Prepared();
			$p->_append($args);
			return $p;
		}

		/* Append using varargs for neater code */
		function append(){
			$this->_append(func_get_args());
		}
		
		/* Append from array to enable forwarding func_get_args from functions that accept varargs */
		function _append($args){
			$this->sql .= $args[0];

			$count = count($args);
			$i=1; 

			while($i<$count){
				$this->args[] = $args[$i];
				$i++;
			}
		}

	}
}
