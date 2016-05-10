<?
$src_dir = 'src';
$out_dir = 'build';
$version = "0.2.0";

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
