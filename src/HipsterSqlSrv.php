<?php

namespace org\hrg\php_hipster_sql{

	class HipsterSqlSrv extends HipsterSql{

		function connect($host,$user,$pass,$db){
			$this->connection = sqlsrv_connect($host,$user,$pass) or $this->qdie('Unable to connect to '.$user.'@'.$host);
			sqlsrv_select_db($db, $this->connection) or $this->qdie('Unable to select database '.$db);
		}

		function error(){
		  return sqlsrv_error($this->connection);
		}

		public function escape($str){
			return sqlsrv_real_escape_string($str);
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
			if($this->result) @sqlsrv_free_result($this->result);
		}

		function close(){
			$this->close_result();
			@sqlsrv_close($this->connection);
		}

		function query($sql){
			if($this->result) @sqlsrv_free_result($this->result);

			$query = func_num_args() > 1 ? func_get_args():$sql;
			$this->last_query = $query;

			$query = $this->build($query);

			return ($this->result = sqlsrv_query($query, $this->connection)) or $this->qdie('QUERY FAILED ');
		}

		function fetch_assoc(){
			return sqlsrv_fetch_assoc($this->result);
		}

		function fetch_row(){
			return sqlsrv_fetch_row($this->result);
		}

		/** execute a query without records returned (UPDATE, DELETE) */
		function update($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);
		}

		/** execute insert statement and return the created id (sqlsrv_insert_id)*/
		function insert($sql){
			$this->query(func_num_args() > 1 ? func_get_args():$sql);

			return sqlsrv_insert_id($this->connection);
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

	}

}
