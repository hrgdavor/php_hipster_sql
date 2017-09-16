<?php

namespace org\hrg\php_hipster_sql{

	class Query{
		var $arr = array();

		function __construct(){
			$this->_append(func_get_args());
			if(count($this->arr) > 0 && (!is_string($this->arr[0]))){
				throw new \Exception('First element of a query must be an sql string. Type is: '.gettype($this->arr[0]).".\n".print_r($this->arr,true));
			}
		}

		static function from_args($args){
			if(count($args) == 1 && $args[0] instanceof Query) return $args[0];

			$q = new Query();
			$q->_append($args);
			return $q;
		}

		function get_query_array(){
			return $this->arr;
		}

		function is_empty(){
			return count($this->arr) == 0;
		}

		/** Append more queries to the existing one. Similar to concat, except it changes the firs array instead of returning new one.*/
		final function append(){
			$this->_append(func_get_args());
			return $this;
		}

		final function append_one($query){
			$this->_append($query);
		}
		
		/** append value part to the query. */ 
		function append_value($val){
			if($val instanceof Query ){// if value is array, then it is actually a query that can be apended as usual
				$this->append($val->arr);
			}else{
				$countLeft = count($this->arr);	
				if($countLeft %2 == 0){// last element is a value
					// this usually should not happen, @TODO consider throwing an error
					$this->arr[] = '';
				}
				$this->arr[] = $val;
			}
		}

		function _append($right){
			if(!count($right)) return;

			$countRight = count($right);
			$countLeft = count($this->arr);

			$j = 0;
			if($countLeft %2 == 1 && !($this->arr[$countLeft-1] instanceof Query) && !($right[0] instanceof Query)){
				// last element is a query string, so concat the last element from $this->arr and first element from $right
				$this->arr[$countLeft-1] .= $right[0];
				$j=1;// move index to 1 to skip that one as it is already added
			}
			for(;$j<$countRight; $j++){
				$this->arr[] = $right[$j];
			}
		}

		function prepare(){
			$params = array('');

			$arr = array();
			$this->_flatten($arr);

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
			$params[0] = $sql;

			return Prepared::from_args($params);
		}

		/* 
		Flatten the query so no nested Query objects are left. 
		The resulting Query must produce same sql as the input Query when build is called on it. 
		This is utility to simplify generating prepared statements, as the preparing code does not have to vorry about nested queries.
		*/
		function flatten(){
			$q = new Query();
			$this->arr = $this->_flatten($q->arr);
			return $q;
		}

		function _flatten(&$left, $right=null){
			if($right === null) $right = $this->arr;

			$countRight = count($right);
			$evenOdd = 0;
			for($i=0; $i<$countRight; $i++){
				
				$countLeft = count($left);
				
				if($right[$i] instanceof Query){
					$this->_flatten($left, $right[$i]->arr);
					$evenOdd = 1; // will be changed to 2 at the end of the loop
				}else if($evenOdd%2 == 0){// right: even: sql code
					
					if($countLeft %2 == 1 && $countLeft > 0){// left: even: sql code
						$left[$countLeft-1] .= $right[$i];
					}else{// left: odd: variable
						$left[] = $right[$i];
					}

				}else{// right: odd: variable

					if($countLeft %2 == 0){// last element is a value
						// this usually should not happen, @TODO consider throwing an error
						$left[] = '';
					}
					$left[] = $right[$i];
				}
				$evenOdd++;
			}

			return $left;
		}
	}

}
