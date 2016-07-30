<?php

namespace {

	/**
		hip_get_db function must be declared that returns the current connection object;
	*/

	function hip_throw_error($val){
	  return hip_get_db()->throw_error($val);
	}

	function hip_error_code(){
	  return hip_get_db()->error_code();
	}

	function hip_error(){
	  return hip_get_db()->error();
	}

	function hip_get_info(){
	  return hip_get_db()->get_info();
	}

	function hip_get_info_str(){
	  return hip_get_db()->get_info_str();
	}

	function hip_last_query(){
	  return hip_get_db()->last_query();
	}

	function hip_escape($str){
		return hip_get_db()->escape($str);
	}

	function hip_q($sql=null){
		return hip_get_db()->q(func_num_args() == 1 ? $sql:func_get_args());
	}	

	function hip_q_column($str){
		return hip_get_db()->q_column($str);
	}

	function hip_q_table($str){
		return hip_get_db()->q_table($str);
	}

	function hip_q_value($str){
		return hip_get_db()->q_value($str);
	}

	function hip_concat($arr){
		return hip_get_db()->concat(func_num_args() > 1 ? func_get_args():$arr );
	}

	function hip_implode($glue, $arr, $prefix='', $suffix=''){
		return hip_get_db()->implode($glue, $arr, $prefix, $suffix);
	}

	function hip_implode_values($glue, $arr, $prefix='', $suffix=''){
		return hip_get_db()->implode_values($glue, $arr, $prefix, $suffix);
	}

	function hip_prepare($sql){
		return hip_get_db()->prepare(func_num_args() > 1 ?  func_get_args():$sql);
	}

	function hip_build($sql){
		return hip_get_db()->build(func_num_args() > 1 ? func_get_args():$sql);
	}

	function hip_build_insert($tableName, $values){
		return hip_get_db()->build_insert($tableName, $values);
	}

	function hip_build_update($tableName, $values, $filter){
        if(func_num_args() > 3) $filter = array_slice(func_get_args(),2);
		return hip_get_db()->build_update($tableName, $values, $filter);
	}

	function hip_close(){
		hip_get_db()->close();
	}

	function hip_query($sql){
		hip_get_db()->query(func_num_args() > 1 ? func_get_args():$sql);
	}

	function hip_fetch_assoc(){
		return hip_get_db()->fetch_assoc();
	}

	function hip_fetch_row(){
		return hip_get_db()->fetch_row();
	}

	function hip_update($sql){
		hip_get_db()->update(func_num_args() > 1 ? func_get_args():$sql);
	}

	function hip_insert($sql){
		return hip_get_db()->insert(func_num_args() > 1 ? func_get_args():$sql);
	}

	function hip_rows($sql){
		return hip_get_db()->rows(func_num_args() > 1 ? func_get_args():$sql);
	}

	function hip_rows_limit($from, $limit, $sql){
		if(func_num_args() > 3) $sql = array_slice(func_get_args(),2);
		return hip_get_db()->rows_limit($from, $limit, $sql);
	}

	function hip_row($sql){
		return hip_get_db()->row(func_num_args() > 1 ? func_get_args():$sql);
	}

	function hip_column($sql){
		return hip_get_db()->column(func_num_args() > 1 ? func_get_args():$sql);
	}

	function hip_one($sql){
		return hip_get_db()->one(func_num_args() > 1 ? func_get_args():$sql);
	}

}