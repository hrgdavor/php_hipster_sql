<?php
require_once "simple_test.php";

require_once "../src/HipsterSql.php";
require_once "../src/hipster_sql.php";

$DB = new org\hrg\php_hipster_sql\HipsterSql();

function hip_get_db(){ global $DB; return $DB;} // required for procedural style

assert_equal(
	hip_build('select id,name from users where id=',1),
	'select id,name from users where id=1'
);

assert_equal(
	hip_build('select id,','name',' from users where id=',1),
	'select id,\'name\' from users where id=1'
);

/** TEST concat */

assert_equal(
	hip_build(hip_concat('select id,name ','from users where id=1')),
	'select id,name from users where id=1'
);

assert_equal(
	hip_build(hip_concat(array('select id,name '),array('from users where id=1'))),
	'select id,name from users where id=1'
);

assert_equal(
	hip_build(hip_concat(array('select id,','name',' '),array('from users where id=1'))),
	'select id,\'name\' from users where id=1'
);

assert_equal(
	hip_build(hip_concat(array('select id,','name'),' from users where id=1')),
	'select id,\'name\' from users where id=1'
);

assert_equal(
	hip_build(hip_concat(array('select id,','name',' '),array('from users where id=',1))),
	'select id,\'name\' from users where id=1'
);

assert_equal(
	hip_build(hip_concat(array('select id,','name',' '),array('from users where id=','1a'))),
	'select id,\'name\' from users where id=\'1a\''
);

$userData = ['name'=>'John','email'=>'john@gmail.com'];

$id = 1;
$password = 'reek';
assert_equal(
	hip_build( hip_build_update('users',$userData, 'id=',$id) ),
	"UPDATE \"users\" SET \"name\"='John', \"email\"='john@gmail.com' WHERE id=1"
);

$userData = ['name'=>'John','email'=>'john@gmail.com', 'password'=>array('PASSWORD(',$password,')') ];

$id = 1;
assert_equal(
	hip_build( hip_build_update('users',$userData, 'id=',$id) ),
	"UPDATE \"users\" SET \"name\"='John', \"email\"='john@gmail.com', \"password\"=PASSWORD('reek') WHERE id=1"
);

echo "<b>Test success: $__TEST_COUNT</b>";	

