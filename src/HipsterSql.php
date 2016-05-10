<?

namespace org\hrg\php_hipster_sql{

	/*
	Base class that database specific implementations will exted.
	*/
	class HipsterSql{
		protected $last_query;
		protected $result;
		protected $connection;

		protected $columQuote1 = '"';
		protected $columQuote2 = '"';

		/* Last query that was executed. Usefull when troubleshooting */
		public function last_query(){
			if(is_array($this->last_query))
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
			$ret = array();
			$first = true;

			for($i=0; $i<$count; $i++){
				if(!count($arr[$i])) continue;

				if(!$first) $this->_append($ret,$glue);
				$this->_append($ret,$arr[$i]);

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

			return $ret;
		}

		/** Concatenate 2 or more queries. When concatenating 2 prepared statements (arrays in our case) we need to make sure that
		the resulting array maintains the requirement for the prepared statement arrays. The requirement is that variables must
		be at odd indices in the array. Simply appending the arrays can result in malformed array. <br>
		Also handles concating string queries with prepared statements (arrays). If singel argument is provided it is interpreted as
		that argument contains an array with queries we want to concatenate */
		final function concat($arr){
			$ret = array();
			return $this->_append_all( $ret, func_num_args() > 1 ?  func_get_args():$arr );
		}

		/** Append more queries to the existing one. Similar to concat, except it changes the firs array instead of returning new one.*/
		final function append(&$left){
			return $this->_append_all($left, array_slice(func_get_args(),1) );
		}

		function _append_all(&$left, $arr){
			$count = count($arr);
			for($i=0; $i<$count; $i++){
				$this->_append($left, $arr[$i]);
			}
			return $left;
		}

		/** append value part to the query. */ 
		function _append_value(&$left, $val){
			if(is_array($val)){// if value is array, then it is actually a query that can be apended as usual
				$this->append($left, $val);
			}else{
				$countLeft = count($left);	
				if($countLeft %2 == 0){// last element is a value
					// this usually should not happen, @TODO consider throwing an error
					$left[] = '';
				}
				$left[] = $val;
			}
		}

		function _append(&$left, $right){
			if(!is_array($right)) $right = array($right);// less cases to handle :)

			$countRight = count($right);
			$countLeft = count($left);

			$j = 0;
			if($countLeft %2 == 1){// last element is a query string, so concat the last element from $left and first element from $right
				$left[$countLeft-1] .= $right[0];
				$j=1;// move index to 1 to skip that one as it is already added
			}

			for(;$j<$countRight; $j++){
				$left[] = $right[$j];
			}
		}

		/* Flatten the query so no nested arrays are left. The resulting array must produce same query as the input array
		when build is called on it. This is utility to simplify generating prepared statements, as the preparing code does not have to vorry
		about nested arrays.*/
		function flatten($sql){
			if(is_array($sql)){
				$left = array();
				$this->_flatten($left, $sql);
				return $left;
			}else
				return $sql;
		}

		function _flatten(&$left, $right){
			$countRight = count($right);
			
			for($i=0; $i<$countRight; $i++){
				
				$countLeft = count($left);
				
				if(is_array($right[$i])){
					$this->_flatten($left, $right[$i]);
				
				}else if($i%2 == 0){// right: sql code
					
					if($countLeft %2 == 1 && $countLeft > 0){// left: sql code
						$left[$countLeft-1] .= $right[$i];
					}else{// left: variable
						$left[] = $right[$i];
					}

				}else{// right: variable

					if($countLeft %2 == 0){// last element is a value
						// this usually should not happen, @TODO consider throwing an error
						$left[] = '';
					}
					$left[] = $right[$i];
				}

			}

			return $left;
		}

		function prepare($sql){
			$params = array();
			
			$sql = $this->flatten($sql);

			if(is_array($sql) && count($sql>1)){
				
				$query = $sql[0];
				$count = count($sql);
				for($i=1; $i<$count; $i++){
					if($i%2 == 0) 
						$query .= $sql[$i];
					else {
						$query .= '?';
						$params[] = $sql[$i];
					}
				}
			}else{
				$query = $this->build($sql);
			}
			return array($query, $params);
		}

		function build($query){
			if(func_num_args() > 1) $query = func_get_args();

			if(is_array($query)){
				
				$count = count($query);
				if($count == 0) return '';
				if($count == 1) return $this->build($query[0]);

				$ret = $query[0];

				for($i=1; $i<$count; $i++){
				
					$queryPart = $query[$i];
				
					if($i%2 == 0) 
						$ret .= $queryPart;
					else if(is_array($queryPart))
						$ret .= $this->build($queryPart);
					else
						$ret .= $this->q_value($queryPart);
				}
				
				return $ret;
			}
			return $query;
		}

		function build_where($op,$arr){
			if(!count($arr)) return array();
			return array('WHERE ', $this->implode(' '.$op.' ',$arr) );
		}

		function build_where_and($arr){
			return $this->build_where('AND',$arr);
		}

		function build_where_or($arr){
			return $this->build_where('OR',$arr);
		}

		function build_and($arr){
			if(!count($arr)) return array();
			return array('( ', $this->implode(' AND ',$arr), ' )' );
		}

		function build_or($arr){
			if(!count($arr)) return array();
			return array('( ', $this->implode(' OR ',$arr), ' )' );
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
			return $v;
		}

		/** generate update statement out of an array */
		function build_update($tableName, $values, $filter){
	        if(func_num_args() > 3) $filter = array_slice(func_get_args(),2);

			$ret = array();
			$delim = '';
			foreach($values as $field=>$value){
				if($delim){
					$ret[] =$delim.$this->q_column($field).'=';
				}else{
					$ret[] = 'UPDATE '.$this->q_table($tableName).' SET '.$this->q_column($field).'=';
				} 
				
				$ret[] = $value;
				$delim = ', ';
			}
			// filter is not optional on purpose to avoid accidental update on whole table 
			if($filter != 'all_rows'){
				$ret[] = ' WHERE ';
				$ret = $this->concat($ret, $filter);
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