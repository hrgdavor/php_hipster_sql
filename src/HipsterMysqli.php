<?php

namespace org\hrg\php_hipster_sql{

	class HipsterMysqli extends HipsterSql{

		function connect($host,$user,$pass,$db){
			$this->connection = mysqli_connect($host,$user,$pass,$db)or $this->qdie('Unable to connect to '.$user.'@'.$host.' and database '.$db);
		}

		function error(){
		  return $this->connection->error;
		}

		function error_code(){
		  return $this->connection->errno;
		}

		public function escape($str){
			return $this->connection->real_escape_string($str);
		}

		/* Convert to valid column name and escape if needed */
		function q_column($str){
			return '`'.$this->escape($str).'`';
		}

		/* Convert to valid column name */
		function q_table($str){
			return '`'.$this->escape($str).'`';
		}

		function close_result(){
			if($this->result){
				@$this->result->close();
				$this->result = null;
			}
		}

		function close(){
			$this->close_result();
			@$this->connection->close();
		}

		function query($sql){
			$this->close_result();

			$query = func_num_args() > 1 ? func_get_args():$sql;
			$this->last_query = $query;

			$query = $this->build($query);

			return ( $this->result = $this->connection->query($query) ) or $this->qdie('QUERY FAILED');
		}

		function fetch_assoc(){
			return $this->result->fetch_assoc();
		}

		function fetch_row(){
			return $this->result->fetch_row();
		}

		/** execute a query without records returned (UPDATE, DELETE) */
		function update($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);
		}

		/** execute insert statement and return the created id (mysqli_insert_id)*/
		function insert($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			return mysqli_insert_id($this->connection);
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

			$query = $this->build($sql);

			return $this->rows($query.' LIMIT '.$from.',', $limit);
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