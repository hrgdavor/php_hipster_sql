<?
$src_dir = dirname(__FILE__).'/src';
$out_dir = dirname(__FILE__).'/build';
$version = "0.2.1";

if(!file_exists($out_dir)){
	mkdir($out_dir);
}

$req['hipster_sql.php']  = array('hipster_map.php');
$req['HipsterSql.php']  = array('Prepared.php','Query.php');
// dependencies
$req['HipsterMysql.php']  = array('HipsterSql.php');
$req['HipsterMysqli.php'] = array('HipsterSql.php');
$req['HipsterSqlSrv.php'] = array('HipsterSql.php');
$req['HipsterPdo.php']    = array('HipsterSql.php');

$req['HipsterPdoMysql.php'] = array('HipsterPdo.php');
$req['HipsterPdoPg.php']    = array('HipsterPdo.php');

// build outputs

$todo['combined.HipsterMysql.php'] = array('HipsterMysql.php');
$todo['totally.HipsterMysql.php']  = array('hipster_sql.php','HipsterMysql.php');

$todo['combined.HipsterMysqli.php'] = array('HipsterMysqli.php');
$todo['totally.HipsterMysqli.php']  = array('hipster_sql.php','HipsterMysqli.php');

$todo['combined.HipsterSqlSrv.php'] = array('HipsterSqlSrv.php');
$todo['totally.HipsterSqlSrv.php']  = array('hipster_sql.php','HipsterSqlSrv.php');

$todo['combined.HipsterPdoMysql.php'] = array('HipsterPdoMysql.php');
$todo['totally.HipsterPdoMysql.php']  = array('hipster_sql.php','HipsterPdoMysql.php');

$todo['combined.HipsterPdoPg.php'] = array('HipsterPdoPg.php');
$todo['totally.HipsterPdoPg.php']  = array('hipster_sql.php','HipsterPdoPg.php');

echo '<pre>';

foreach ($todo as $out_file => $deps) {
	$all_deps = all_deps($deps);
	$str = $out_file." GENERATED FROM: \n - ".implode("\n - ", $all_deps);
	echo $str."\n";
	$out_ful_path = $out_dir .'/'. $out_file;

	$str = "<?php\n/*\nVersion $version $str\n*/\n";
	$oldData = "";
	if(file_exists($out_ful_path)) $oldData = file_get_contents($out_ful_path);

	foreach ($all_deps as $dep) {
		$str .= "\n/*\n$dep\n*/\n";
		$lines = file($src_dir.'/'.$dep);
		array_shift($lines);
		foreach ($lines as $line) {
			$str .= $line;
		}
	}
	
	if($str == $oldData){
		echo "<i style=\"color:gray\">Skiiping write, identical content</i>\n";
	}else{
		$fp = fopen($out_ful_path,'w');
		fputs($fp,$str);
		fclose($fp);
	}
	echo "\n";
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
