<?php
require_once "simple_test.php";

require_once "../src/Query.php";
require_once "../src/HipsterSql.php";
// all tests are duplicated here from run.Base.procedural.php to make sure both vversions work and no parameters are lost

$DB = new org\hrg\php_hipster_sql\HipsterSql();



assert_equal(
	$DB->build('select id,name from users where id=',1),
	'select id,name from users where id=1'
);

assert_equal(
	$DB->build('select id,','name',' from users where id=',1),
	'select id,\'name\' from users where id=1'
);

/** TEST concat */
assert_equal(
	$DB->build($DB->concat('select id,name ','from users where id=1')),
	'select id,name from users where id=1'
);

assert_equal(
	$DB->build($DB->concat(array('select id,name '),array('from users where id=1'))),
	'select id,name from users where id=1'
);

assert_equal(
	$DB->build($DB->concat(array('select id,','name',' '),array('from users where id=1'))),
	'select id,\'name\' from users where id=1'
);

assert_equal(
	$DB->build($DB->concat(array('select id,','name'),' from users where id=1')),
	'select id,\'name\' from users where id=1'
);

assert_equal(
	$DB->build($DB->concat(array('select id,','name',' '),array('from users where id=',1))),
	'select id,\'name\' from users where id=1'
);

assert_equal(
	$DB->build($DB->concat(array('select id,','name',' '),array('from users where id=','1a'))),
	'select id,\'name\' from users where id=\'1a\''
);

/** TEST implode */
assert_equal(
	$DB->build( $DB->implode(' AND ', array($DB->q('id=',1) )) ),
	'id=1'
);

assert_equal(
	$DB->build( $DB->implode(' AND ', array($DB->q('id=',1),$DB->q('date>',$DB->q('NOW()'))) ) ),
	'id=1 AND date>NOW()'
);

assert_equal(
	$DB->build( $DB->implode(' AND ', array($DB->q('id=',1),$DB->q('date>','2016-01-01')) ) ),
	'id=1 AND date>\'2016-01-01\''
);

assert_equal(
	$DB->implode(' AND ', array($DB->q()) )->is_empty(),
	true
);

assert_equal(
	$DB->build( $DB->implode(' AND ', array($DB->q()) ) ),
	''
);

/** TEST implode_values */
assert_equal(
	$DB->implode_values(',', array(1,2,3) )->get_query_array(),
	array('',1,',',2,',',3)
);

$tmp = $DB->implode_values(',', array(1,2,3) );
assert_equal(
	$DB->build('id IN (', $tmp, ')' ),
	"id IN (1,2,3)"
);


/** TEST flatten */
assert_equal(
	$DB->q( 'WHERE id=', 1, ' AND pasword=', $DB->q('PASSWORD(','aaa',')') ) ->flatten()->get_query_array(),
	array('WHERE id=', 1, ' AND pasword=PASSWORD(','aaa',')') 
);

/** TEST prepare */
assert_equal(
	$DB->prepare( array('WHERE id=', 1 ) ),
	array('WHERE id=?',array(1))
);

assert_equal(
	$DB->prepare( array('WHERE id=', 1, ' AND is_deleted=',0 ) ),
	array('WHERE id=? AND is_deleted=?',array(1,0))
);

$userData = ['name'=>'John','email'=>'john@gmail.com'];

$id = 1;
$password = 'reek';

assert_equal(
	$DB->build( $DB->build_update('users',$userData, 'id=',$id) ),
	"UPDATE \"users\" SET \"name\"='John', \"email\"='john@gmail.com' WHERE id=1"
);

$userData = ['name'=>'John','email'=>'john@gmail.com', 'password'=>$DB->q('PASSWORD(',$password,')') ];

$id = 1;
assert_equal(
	$DB->build( $DB->build_update('users',$userData, 'id=',$id) ),
	"UPDATE \"users\" SET \"name\"='John', \"email\"='john@gmail.com', \"password\"=PASSWORD('reek') WHERE id=1"
);

echo "<b>Test success: $__TEST_COUNT</b>";	

