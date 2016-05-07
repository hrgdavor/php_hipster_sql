<?
$src_dir = 'src';
$out_dir = 'build';

if(!file_exists($out_dir)){
	mkdir($out_dir);
}

// dependencies
$req['HipsterMysql.php']  = array('HipsterSql.php');
$req['HipsterMysqli.php'] = array('HipsterSql.php');
$req['HipsterSqlSrv.php'] = array('HipsterSql.php');
$req['HipsterPdo.php']    = array('HipsterSql.php');

$req['HipsterPdoMysql.php'] = array('HipsterPdo.php');
$req['HipsterPdoPg.php']    = array('HipsterPdo.php');

// build outputs

$todo['combined.HipsterMysql.php'] = array('HipsterMysql.php');
$todo['totally.HipsterMysql.php']  = array('HipsterMysql.php','hipster_sql.php');

$todo['combined.HipsterMysqli.php'] = array('HipsterMysqli.php');
$todo['totally.HipsterMysqli.php']  = array('HipsterMysqli.php','hipster_sql.php');

$todo['combined.HipsterSqlSrv.php'] = array('HipsterSqlSrv.php');
$todo['totally.HipsterSqlSrv.php']  = array('HipsterSqlSrv.php','hipster_sql.php');

$todo['combined.HipsterPdoMysql.php'] = array('HipsterPdoMysql.php');
$todo['totally.HipsterPdoMysql.php']  = array('HipsterPdoMysql.php','hipster_sql.php');

$todo['combined.HipsterPdoPg.php'] = array('HipsterPdoPg.php');
$todo['totally.HipsterPdoPg.php']  = array('HipsterPdoPg.php','hipster_sql.php');

echo '<pre>';

foreach ($todo as $out_file => $deps) {
	$all_deps = all_deps($deps);
	$str = $out_file." GENERATED FROM: \n - ".implode("\n - ", $all_deps);
	echo $str."\n\n";

	$fp = fopen($out_dir .'/'. $out_file,'w');
	fputs($fp,"<?php\n/*\n$str\n*/\n");
	foreach ($all_deps as $dep) {
		fputs($fp,"\n/*\n$dep\n*/\n");
		$lines = file($src_dir.'/'.$dep);
		array_shift($lines);
		foreach ($lines as $line) {
			fputs($fp,$line);
		}
	}
	fclose($fp);
}




function all_deps($deps){ global $req;
	$all_deps = array();
	foreach ($deps as $dep) {
		add_deps($all_deps, $dep);
	}
	return $all_deps;
}

function add_deps(&$all_deps, $dep){ global $req;
	if(!in_array($dep, $all_deps)){
		array_unshift($all_deps, $dep);
		if($req[$dep]){
			foreach ($req[$dep] as $req_dep) {
				add_deps($all_deps, $req_dep);
			}
		}
	}
	return $all_deps;
}
