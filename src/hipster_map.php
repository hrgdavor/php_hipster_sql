<?php

namespace {

	function hip_get_column($rows,$column){
		$colData = array();
		if(is_array($rows)) foreach($rows as $row){
			$colData[] = $row[$column];
		}
		return $colData;
	}

	function hip_map($rows,$column=null){
		if($column && !is_array($column)) $column = array_slice(func_get_args(),1);
		
		$map = array();
		
		if(is_array($rows) && count($rows)){
			if($column){
				foreach($rows as $row){
					hip_map_assoc_fill($map,$row,$column,0);
				}
			}else{
				$column = array_keys($rows[0]);
				foreach($rows as $row){
					hip_map_fill($map,$row, $column, 0);
				}
			}
		}

		return $map;
	}

	function hip_map_list($rows,$column=null){
		if($column && !is_array($column)) $column = array_slice(func_get_args(),1);
		
		$map = array();
		
		if(is_array($rows) && count($rows)){
			if($column){
				foreach($rows as $row){
					hip_map_assoc_list_fill($map,$row,$column, 0);
				}
			}else{
				$column = array_keys($rows[0]);
				foreach($rows as $row){
					hip_map_list_fill($map,$row, $column, 0);
				}
			}
		} 
		
		return $map;
	}

	/*                 UTILITY recursive functions used above               */


	/** Utility function that can be called for each row to fill the map with values.
		The map is deep #columns-1 and the last column contains leaf values. */
	function hip_map_fill(&$map,&$row,$column,$i){
		if($i>=count($column)-2)
			// time to put the value in the last level
			$map[$row[$column[$i]]] = $row[$column[$i+1]];
		else
			hip_map_fill($map[$row[$column[$i]]], $row,$column,$i+1);
	}

	/** Utility function that can be called for each row to fill the map with values. 
		The map is deep #columns-1 and the last column contains leaf values.
		Expects each leaf to possibly have more than one element for the same ekys, so leafs are arrays of final values. */
	function hip_map_list_fill(&$map,&$row,$column,$i){
		if($i>=count($column)-2) 
			// time to put the value in the array on the last level
			$map[$row[$column[$i]]][] = $row[$column[$i+1]];
		else 
			hip_map_list_fill($map[$row[$column[$i]]], $row,$column,$i+1);
	}

	/* Multi-level map fill utility function. Each leaf is a row  */
	function hip_map_assoc_fill(&$map,&$row,$column,$i){
		if($i>=count($column)-1) 
			// time to put the row in the last level
			$map[$row[$column[$i]]] = $row;
		else 
			hip_map_assoc_fill($map[$row[$column[$i]]], $row,$column,$i+1);
	}

	/* Multi-level map fill utility function. Expects each leaf to possibly have more than one element for the same keys, so leafs are arrays of rows */
	function hip_map_assoc_list_fill(&$map,&$row,$column,$i){
		if($i>=count($column)-1) 
			// time to put the row in the array on the last level
			$map[$row[$column[$i]]][] = $row;
		else 
			hip_map_assoc_fill_list($map[$row[$column[$i]]], $row,$column,$i+1);
	}


}