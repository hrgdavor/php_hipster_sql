<?php

namespace org\hrg\php_hipster_sql{

	/*
	Base class that database specific implementations will extend.
	*/
	class HipsterSql{
		protected $last_query;
		protected $result;
		protected $connection;

		protected $columQuote1 = '"';
		protected $columQuote2 = '"';
		protected $throwError = true;

		public function set_connection($conn){
			$this->connection = $conn;
		}

		public function throw_error($val){
			$old = $this->throwError;
			$this->throwError = $val;
			return $old;
		}

		/* Last query that was executed. Usefull when troubleshooting */
		public function last_query(){
		    return $this->last_query; 
		}

		public function last_query_str(){
		    return $this->build($this->last_query);
		}

		/* Function that escapes string values for the #value function. Subclasses should override this
		to implement database specific function that makes strings safe for query concatenation. */
		function escape($str){
			return addslashes($str);
		}

		function quote($str){
			return "'".$this->escape($str)."'";
		}

		function q($sql=null){
			return new Query(func_num_args() != 1 ? func_get_args():$sql);
		}

		/* Sanitize a value and make it ready for concat*/
		function q_value($str){
			if( $str === "NULL" || is_null($str) ) return "NULL";
			if(is_numeric($str) && $str[0] != "0" && $str[0] != "+") return $str;//nubers that have consistent representation
			return $this->quote($str);
		}
		

		/* Convert to valid column name */
		function q_column($str){
			return $this->columQuote1.$this->escape($str).$this->columQuote2;
		}

		/* Convert to valid table name */
		function q_table($str){
			return $this->columQuote1.$this->escape($str).$this->columQuote2;
		}

		/* Three different variants of paramters
		   $glue,$arr
		   $prefix,$glue,$arr
		   $prefix,$glue,$arr,$suffix
		*/
		final function implode($glue, $arr, $prefix='', $suffix=''){
			if(func_num_args()>2 && $prefix !==''){
				$glue = $arr; // arg1
				$arr = $prefix; // arg2
				$prefix = func_get_arg(0);
			}
			$count = count($arr);
			$ret = new Query();
			$first = true;

			for($i=0; $i<$count; $i++){
				if(!($arr[$i] instanceof Query)) throw new \Exception("only queries can be imploded");
				if($arr[$i]->is_empty()) continue;

				if($first)
					$ret->append_one($prefix);
				else 
					$ret->append_one($glue);
				
				$ret->append_one($arr[$i]);

				$first = false;
			}
			if($suffix) $ret->append_one($suffix);

			return $ret;
		}

		/* Three different variants of paramters
		   $glue,$arr
		   $prefix,$glue,$arr
		   $prefix,$glue,$arr,$suffix
		*/
		final function implode_values($glue, $arr, $prefix='', $suffix=''){
			if(func_num_args()>2 && $prefix !==''){
				$glue = $arr; // arg1
				$arr = $prefix; // arg2
				$prefix = func_get_arg(0);
			}

			$count = count($arr);
			$ret = array($prefix);

			for($i=0; $i<$count; $i++){
				if($i>0) $ret[] = $glue;
				$ret[] = $arr[$i];
			}
			
			$ret = new Query($ret);
			if($suffix) $ret->append_one($suffix);

			return $ret;
		}

		function prepare($sql){
			if(!(func_num_args() == 1 && $sql instanceof Query))
				$sql = new Query(func_get_args());
			$params = array();

			$sql->flatten();

			$arr = $sql->get_query_array();
			$sql = $arr[0];
			$count = count($arr);
			for($i=1; $i<$count; $i++){
				if($i%2 == 0) 
					$sql .= $arr[$i];
				else {
					$sql .= '?';
					$params[] = $arr[$i];
				}
			}

			return array($sql, $params);
		}

		function build($query){
			if(func_num_args() > 1) $query = func_get_args();
			if($query instanceof Query) $query = $query->get_query_array();

			if(is_array($query)){
				
				$count = count($query);
				if($count == 0) return '';
				if($count == 1) return $this->build($query[0]);

				$ret = $query[0] instanceof Query ? '':$query[0];

				$evenOdd = 1;
				for($i=1; $i<$count; $i++){
				
					$queryPart = $query[$i];
				
					if($queryPart instanceof Query){ // array instead of value is not allowed, as it would enable easy SQL injection 
						$ret .= $this->build($queryPart->get_query_array());
						$evenOdd = 1; // will be changed to 2 at the end of the loop.
					}else if($evenOdd %2 == 0) 
						$ret .= $queryPart; // all even index parts must be strings
					else{
						$ret .= $this->q_value($queryPart);
					}

					$evenOdd++;
				}
				
				return $ret;
			}
			return $query;
		}

		function build_insert($tableName, $values){
			$ret = 'INSERT INTO '.$this->q_table($tableName).'(';
			$v = array();
			$delim = '';
			foreach($values as $field=>$value){
				$ret .= $delim.$this->q_column($field);
				if($delim) $v[] = $delim;
				$v[] = $value;
				$delim = ', ';
			}
			$ret .= ') VALUES(';

			$v[] = ')';
			array_unshift($v,$ret);
			return new Query($v);
		}

		/** generate update statement out of an array */
		function build_update($tableName, $values, $filter){
	        if(func_num_args() > 3) $filter = array_slice(func_get_args(),2);

			$ret = array();
			$delim = '';
			foreach($values as $field=>$value){
				$ret[] =$delim.$this->q_column($field).'=';

				if(!$delim){
					// firts iteration we start the first part of the query with the first SET
					$ret[0] = 'UPDATE '.$this->q_table($tableName).' SET '.$ret[0];
				} 
				
				$ret[] = $value;
				$delim = ', ';
			}

			$ret = new Query($ret);

			// filter is not optional on purpose to avoid accidental update on whole table 
			if($filter != 'all_rows'){
				$ret->append(' WHERE ');
				$ret->append($filter);
			}

			return $ret;
		}

		function error_code(){ return 0;}
		function error(){ return '';}

		function get_info(){
			$last_query = $this->last_query();
			if($last_query && $last_query instanceof Query) $last_query = $last_query->get_arr();

			$ret = array(
				'code'=>$this->error_code(),
				'last_query_str'=>$this->last_query_str(),
				'last_query'=>$last_query
			);
			$error = $this->error();
			if($error){
				$ret['error'] = $error;
			}
			return $ret;
		}

		function get_info_str($html=false){
			$info = $this->get_info();
			if($html){
				$str = "<pre>\n";
				foreach($info as $key=>$val){
					$str .= "<b>$key</b>\n<div style=\"padding-left: 16px\">";
					$str .= htmlspecialchars(print_r($val,true));
					$str .= "</div>\n";
				}
				$str .= "</pre>\n";
				return $str;

			}else{
				return print_r($info,true);
			}
			
		}

		function qdie($message){
			if($this->throwError === 'die'){			
				echo '<pre> ERROR: '.$message."\n".
					"[".$this->error_code().'] '.$this->error()."\n".
					$this->get_info_str();
				$this->close();
				die();
			}else if($this->throwError === true){
				throw new HipsterException($message, $this->get_info());
			}
		}
	}

	class HipsterException extends \Exception{
		protected $info;

	    // Redefine the exception so message isn't optional
	    public function __construct($message, $info, Exception $previous=null){
	    	$this->info = $info;
	        parent::__construct($message, $info['code'], $previous);
	    }

	    public function get_info(){
	    	return $this->info;
	    }
	}

}
