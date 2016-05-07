<?

namespace org\hrg\php_hipster_sql{

	/*
	Base class that database specific implementations will exted.
	*/
	class HipsterSql{
		var $last_query;
		var $result;
		var $connection;

		/* Last query that was executed. Usefull when troubleshooting */
		public function last_query(){
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
			return '"'.$this->escape($str).'"';
		}

		/* Convert to valid table name */
		function q_table($str){
			return '"'.$this->escape($str).'"';
		}

		/** Concatenate 2 or more queries. When concatenating 2 prepared statements (arrays in our case) we need to make sure that
		the resulting array maintains the requirement for the prepared statement arrays. The requirement is that variables must
		be at odd indices in the array. Simply appending the arrays can result in malformed array. <br>
		Also handles concating string queries with prepared statements (arrays). */
		final function concat(){
			$this->_concat(func_get_args());
		}

		/** Extracted to receive array and not variable parameters. Allows for procedural style functions to use variable parameters (func_get_args).*/
		function _concat($arr){
			
			$left = $arr[0];
			if(!is_array($left)) $left = array($left);// half less cases to handle :)
			
			$count = count($arr);
			for($i=1; $i<$count; $i++){
				$right = $arr[$i];
				if(!is_array($right)) $right = array($right);// even less cases to handle :)

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

			return $left;
		}

		function build($query){
			if(func_num_args() > 1) $query = func_get_args();

			if(is_array($query)){
				$ret = $query[0];
				$count = count($query);
				for($i=1; $i<$count; $i++){
					if($i%2 == 0) 
						$ret .= $query[$i];
					else 
						$ret .= $this->q_value($query[$i]);
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
			return $v;
		}

		/** generate update statement out of an array */
		function build_update($tableName, $values, $filter){
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
				$ret[] = ' WHERE '.$filter;
				$arr = func_get_args();
				$count = count($arr);
				for($i=3; $i<$count; $i++) $ret[] = $arr[$i];
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