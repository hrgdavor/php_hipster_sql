<?php

namespace org\hrg\php_hipster_sql{

	use PDO;

	class HipsterPdo extends HipsterSql{
		protected $db_type = 'mysql';
		protected $last_prepared;

		function __construct($db_type){
			$this->db_type = $db_type;
		}

		function connect($host,$user,$pass,$db,$attr=array()){
			if(!$attr[PDO::ATTR_DEFAULT_FETCH_MODE]) $attr[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
			$this->connection =  new PDO(
	    		$this->db_type.':host='.$host.';dbname='.$db,
	    		$user,
	    		$pass,
	    		$attr
			);
		}

		public function last_query(){
			return parent::last_query()."\n".print_r($this->last_prepared,true);
		}

		function quote($str){
			return $this->connection->quote($str);
		}

		function error(){
			if($this->result)
				$err = $this->result->errorInfo();
			else
				$err = $this->connection->errorInfo();

			if(is_array($err)) return $err[2];
			return $err;
		}

		function close_result(){
			if($this->result){
				@$this->result->closeCursor();
				$this->result = null;
			}
		}

		function close(){
			$this->close_result();
			@$this->connection->close();
		}

		function query($sql){
			$this->close_result();

			if(func_num_args() > 1) $sql = func_get_args();
			
			$this->last_query = $sql;
			list($query, $params) = $this->prepare($sql);

			$this->last_prepared = array($query, $params);
			

			$this->result = $this->connection->prepare($query) or $this->qdie('QUERY: '.$this->last_query());
			$this->result->execute($params) or $this->qdie('QUERY: '.$this->last_query());


			return $this->result;
		}

		function fetch_assoc(){
			return $this->result->fetch(PDO::FETCH_ASSOC);
		}

		function fetch_row(){
			return $this->result->fetch(PDO::FETCH_NUM);
		}

		/** execute a query without records returned (UPDATE, DELETE) */
		function update($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);
		}

		/** execute insert statement and return the created id (mysqli_insert_id)*/
		function insert($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);
			return $this->connection->lastInsertId();
		}

		/** get all rows as an associative array */
		function rows($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$rows = array();
			while($row = $this->fetch_assoc()){
				$rows[] = $row;
			}

			$this->close_result();

			return $rows;
		}

		function rows_limit($from, $limit, $sql){
			if(func_num_args() > 3) $sql = array_slice(func_get_args(),2);

			if($limit == 0) return $this->rows($sql);
			// DIRTY FIX STUPID PDO.. PDO treats parameters as strings, so create the string our selves, instead of using parameters 
			return $this->rows($this->concat($sql,array(' LIMIT '. ($limit+0) .' OFFSET '. ($from+0) )));
		}

		/** get the first row as associative array */
		function row($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$ret = null;
			if($row = $this->fetch_assoc()) $ret = $row;

			$this->close_result();

			return $ret;
		}

		/** get single column */
		function column($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$rows = array();
			while($row = $this->fetch_row()){
				$rows[] = $row[0];
			}

			$this->close_result();

			return $rows;
		}


		function one($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$ret = null;
			if($row = $this->fetch_row()) $ret = $row[0];

			$this->close_result();

			return $ret;
		}

		/** get two or more column records as map $row[0]=>$row[1]:
		array($row1[0]=>$row1[1], $row2[0]=>$row2[1],...)
		#example:
		 * $categs = sql_map("select id,name from category");
		 * echo "category:".$categs[$category_id];
		 * */
		function map($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$rows = array();
			while($row = $this->fetch_assoc()){
				$this->map_fill($rows,$row, 0);
			}

			$this->close_result();

			return $rows;
		}

		/** get two column records as map $row[0]=>array($row[1]...):
		array($row1[0]=>array($row1[1],$row2[1]...) ...)
		#example:
		 * $prodc = sql_map("select product_id,categ_id from product2category");
		 * echo "category:".implode(",",$prodc[$category_id]);
		 * */
		function map_list($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$rows = array();
			while($row = $this->fetch_assoc()){
				$this->map_list_fill($rows,$row, 0);
			}

			$this->close_result();

			return $rows;
		}

		/** get two records as map $row[$column]=>$row:
		array($row1[$column]=>$row1, $row2[$column]=>$row2,...)
		#example:
		 * $categs = sql_map_assoc("select parent_id,id,name from category","id");
		 * echo "category:".$categs[$category_id]["name"];
		 * */
		function map_assoc($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$rows = array();
			while($row = $this->fetch_assoc()){
				$this->map_assoc_fill($rows,$row, 0);
			}

			$this->close_result();

			return $rows;
		}

		function map_assoc_list($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			$rows = array();
			while($row = $this->fetch_assoc()){
				$this->map_assoc_list_fill($rows,$row, 0);
			}

			$this->close_result();

			return $rows;
		}

	}

}