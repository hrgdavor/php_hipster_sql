<?php
require_once "simple_test.php";

require_once "../src/Query.php";
require_once "../src/HipsterSql.php";
require_once "../src/hipster_sql.php";

$DB = new org\hrg\php_hipster_sql\HipsterSql();

function hip_get_db(){ global $DB; return $DB;} // required for procedural style

echo "<pre>\n";



echo "<b>Test success: $__TEST_COUNT</b>";	

