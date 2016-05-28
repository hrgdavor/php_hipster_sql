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

		/* Last query that was executed. Usefull when troubleshooting */
		public function last_query(){
			if($this->last_query instanceof Query)
				return print_r($this->last_query,true)."\n".$this->build($this->last_query);
		    return $this->last_query; 
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
			if( is_object($str) && get_class($str) == "sql_literal") return $str->value;
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

		final function implode($glue, $arr){
			if(func_num_args() >2 ) $arr = array_slice(func_get_args(),1) ;

			$count = count($arr);
			$ret = new Query();
			$first = true;

			for($i=0; $i<$count; $i++){
				if(!($arr[$i] instanceof Query)) throw new \Exception("only queries can be imploded");
				if($arr[$i]->is_empty()) continue;

				if($first)
					$ret->append_one('');
				else 
					$ret->append_one($glue);
				
				$ret->append_one($arr[$i]);

				$first = false;
			}

			return $ret;
		}

		final function implode_values($glue, $arr){
			if(func_num_args() >2 ) $arr = array_slice(func_get_args(),1) ;

			$count = count($arr);
			$ret = array('');

			for($i=0; $i<$count; $i++){
				if($i>0) $ret[] = $glue;
				$ret[] = $arr[$i];
			}

			return new Query($ret);
		}

		/** Concatenate 2 or more queries. When concatenating 2 prepared statements (arrays in our case) we need to make sure that
		the resulting array maintains the requirement for the prepared statement arrays. The requirement is that variables must
		be at odd indices in the array. Simply appending the arrays can result in malformed array. <br>
		Also handles concating string queries with prepared statements (arrays). If singel argument is provided it is interpreted as
		that argument contains an array with queries we want to concatenate */
		final function concat($arr){
			$ret = new Query();
			$ret->_append_all( func_num_args() > 1 ?  func_get_args():$arr );
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

				$ret = $query[0];

				for($i=1; $i<$count; $i++){
				
					$queryPart = $query[$i];
				
					if($i%2 == 0) 
						$ret .= $queryPart; // all even index parts must be strings
					else if($queryPart instanceof Query) // array instead of value is not allowed, as it would enable easy SQL injection 
						$ret .= $this->build($queryPart->get_query_array());
					else
						$ret .= $this->q_value($queryPart);
				}
				
				return $ret;
			}
			return $query;
		}

		function build_where($op,$arr){
			if(!count($arr)) return new Query();
			return new Query('WHERE ', $this->implode(' '.$op.' ',$arr) );
		}

		function build_where_and($arr){
			return $this->build_where('AND',$arr);
		}

		function build_where_or($arr){
			return $this->build_where('OR',$arr);
		}

		function build_and($arr){
			if(!count($arr)) new Query();
			return new Query('( ', $this->implode(' AND ',$arr), ' )' );
		}

		function build_or($arr){
			if(!count($arr)) return new Query();
			return new Query('( ', $this->implode(' OR ',$arr), ' )' );
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


		/** Utility function that can be called for each row to fill the map with values.
			The map is deep #columns-1 and the last column contains leaf values. */
		protected function map_fill(&$rows,&$row,$i){
			if($i>=count($row)-2) $rows[$row[$i]] = $row[$i+1];
			else $this->map_fill($rows[$row[$i]], $row,$i+1);
		}

		/** Utility function that can be called for each row to fill the map with values. 
			The map is deep #columns-1 and the last column contains leaf values.
			Expects each leaf to possibly have more than one element, so leafs are arrays. */
		protected function map_list_fill(&$rows,&$row,$i){
			if($i>=count($row)-2) $rows[$row[$i]][] = $row[$i+1];
			else $this->map_list_fill($rows[$row[$i]], $row,$i+1);
		}

		/* Multi-level map fill utility function. Each leaf is a row  */
		protected function map_assoc_fill(&$rows,&$row,$column,$i){
			if($i>=count($column)-1) $rows[$row[$column[$i]]] = $row;
			else $this->map_assoc_fill($rows[$row[$column[$i]]], $row,$column,$i+1);
		}

		/* Multi-level map fill utility function. (leafs are arrays) */
		protected function map_assoc_fill_list(&$rows,&$row,$column,$i){
			if($i>=count($column)-1) $rows[$row[$column[$i]]][] = $row;
			else $this->map_assoc_fill_list($rows[$row[$column[$i]]], $row,$column,$i+1);
		}

		function make_map($arr){
			$rows = array();
			foreach($arr as $row){
				_sql_map_fill($rows,$row, 0);
			}
			return $rows;
		}

		function qdie($message){
			echo 'ERROR: '.$message.'<br>'.$this->error().'<br>';
			$this->close();
			die();
		}
	}

}
