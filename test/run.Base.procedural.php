<?php
require_once "simple_test.php";

require_once "../src/Query.php";
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

/** TEST implode */
assert_equal(
	hip_build( hip_implode(' AND ', array(hip_q('id=',1)) ) ),
	'id=1'
);

assert_equal(
	hip_build( hip_implode(' AND ', array(hip_q('id=',1),hip_q('date>',hip_q('NOW()'))) ) ),
	'id=1 AND date>NOW()'
);

assert_equal(
	hip_build( hip_implode(' AND ', array(hip_q('id=',1),hip_q('date>','2016-01-01')) ) ),
	'id=1 AND date>\'2016-01-01\''
);

assert_equal(
	hip_implode(' AND ', array(hip_q()) )->is_empty() ,
	true
);

assert_equal(
	hip_build( hip_implode(' AND ', array(hip_q()) ) ),
	''
);

/** TEST implode_values */
assert_equal(
	hip_implode_values(',', array(1,2,3) )->get_query_array(),
	array('',1,',',2,',',3)
);

$tmp = hip_implode_values(',', array(1,2,3) );
assert_equal(
	hip_build('id IN (', $tmp, ')' ),
	"id IN (1,2,3)"
);


/** TEST flatten */
assert_equal(
	hip_q( 'WHERE id=', 1, ' AND pasword=', hip_q('PASSWORD(','aaa',')') ) ->flatten()->get_query_array(),
	array('WHERE id=', 1, ' AND pasword=PASSWORD(','aaa',')') 
);

/** TEST prepare */
assert_equal(
	hip_prepare( array('WHERE id=', 1 ) ),
	array('WHERE id=?',array(1))
);

assert_equal(
	hip_prepare( array('WHERE id=', 1, ' AND is_deleted=',0 ) ),
	array('WHERE id=? AND is_deleted=?',array(1,0))
);

$userData = ['name'=>'John','email'=>'john@gmail.com'];

$id = 1;
$password = 'reek';

assert_equal(
	hip_build( hip_build_update('users',$userData, 'id=',$id) ),
	"UPDATE \"users\" SET \"name\"='John', \"email\"='john@gmail.com' WHERE id=1"
);

$userData = ['name'=>'John','email'=>'john@gmail.com', 'password'=>hip_q('PASSWORD(',$password,')') ];

$id = 1;
assert_equal(
	hip_build( hip_build_update('users',$userData, 'id=',$id) ),
	"UPDATE \"users\" SET \"name\"='John', \"email\"='john@gmail.com', \"password\"=PASSWORD('reek') WHERE id=1"
);

echo "<b>Test success: $__TEST_COUNT</b>";	

