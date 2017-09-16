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

		function q(){
			return Query::from_args(func_get_args());
		}

		function prepare(){
			return Prepared::from_args(func_get_args());
		}

		/* Sanitize a value and make it ready for concat into a query string */
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
					$ret->append($prefix);
				else 
					$ret->append($glue);
				
				$ret->append($arr[$i]);

				$first = false;
			}

			if($suffix) $ret->append($suffix);

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
			
			$ret = Query::from_args($ret);
			if($suffix) $ret->append($suffix);

			return $ret;
		}

		function build(){
			$arr = Query::from_args(func_get_args())->get_query_array();
				
			$count = count($arr);
			if($count == 0) return '';

			$ret = '';

			$evenOdd = 0;
			for($i=0; $i<$count; $i++){
				$part = $arr[$i];
			
				if($part instanceof Query){ // array instead of value is not allowed, as it would enable easy SQL injection 
					$ret .= $this->build($part);
					$evenOdd = 1; // will be changed to 2 at the end of the loop.
				}else if($evenOdd %2 == 0) 
					$ret .= $part; // all even index parts must be strings
				else{
					$ret .= $this->q_value($part);
				}

				$evenOdd++;
			}
			
			return $ret;
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
			return Query::from_args($v);
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

			$ret = Query::from_args($ret);

			// filter is not optional on purpose to avoid accidental update on whole table 
			if($filter != 'all_rows'){
				$ret->append(' WHERE ');
				$ret->_append($filter);
			}

			return $ret;
		}

		function error_code(){ return 0;}
		function error(){ return '';}

		function get_info(){
			$last_query = $this->last_query();
			if($last_query && $last_query instanceof Query) $last_query = $last_query->get_query_array();

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
			$err_info = "[".$this->error_code().'] '.$this->error();
			if($this->throwError === 'die'){			
				echo '<pre> ERROR: '.$message."\n".$err_info."\n".$this->get_info_str();
				debug_print_backtrace();
				$this->close();
				die();
			}else if($this->throwError === true){
				throw new HipsterException($message.' '.$err_info, $this->get_info());
			}
		}
	}

	class HipsterException extends \Exception{
		protected $info;

	    // Redefine the exception so message isn't optional
	    public function __construct($message, $info, Exception $previous=null){
	    	$this->info = $info;
	    	if($info['last_query_str']) $message .= ". Last query: ".$info['last_query_str'];
	        parent::__construct($message, 0, $previous);
	    }

	    public function get_info(){
	    	return $this->info;
	    }
	}

}
