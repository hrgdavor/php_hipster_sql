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
			try{
				$this->connection =  new PDO(
		    		$this->db_type.':host='.$host.';dbname='.$db,
		    		$user,
		    		$pass,
		    		$attr
				);
			} catch ( \PDOException $e){
				$this->exception = $e;
				$this->qdie('Error connecting to '.$db.'@'.$host.' as '.$user);
			}
		}

		public function last_prepared(){
			return $this->last_prepared;
		}

		public function get_info(){
			$ret = parent::get_info();
			$ret['last_prepared'] = $this->last_prepared();
			return $ret;
		}


		function quote($str){
			return $this->connection->quote($str);
		}

		function error(){
			if($this->result)
				$err = $this->result->errorInfo();
			else if($this->connection)
				$err = $this->connection->errorInfo();
			else if($this->exception)
				$err = $this->exception->getMessage();

			if(is_array($err)) return $err[2];
			return $err;
		}

		function error_code(){
			if($this->result)
				$err = $this->result->errorCode();
			else if($this->connection)
				$err = $this->connection->errorCode();
			else if($this->exception)
				$err = 1;// for now only when connecting

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
			

			$this->result = $this->connection->prepare($query) or $this->qdie('QUERY PREPARE FAILED');
			$this->result->execute($params) or $this->qdie('QUERY FAILED');


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

	}

}