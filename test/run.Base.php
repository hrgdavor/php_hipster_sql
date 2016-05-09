<?php
require_once "simple_test.php";

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
	$DB->build( $DB->implode(' AND ', array(array('id=',1)) ) ),
	'id=1'
);

assert_equal(
	$DB->build( $DB->implode(' AND ', array(array('id=',1),array('date>',array('NOW()'))) ) ),
	'id=1 AND date>NOW()'
);

assert_equal(
	count( $DB->implode(' AND ', array(array()) ) ),
	0
);

assert_equal(
	$DB->build( $DB->implode(' AND ', array(array()) ) ),
	''
);

/** TEST implode_values */
assert_equal(
	$DB->implode_values(',', array(1,2,3) ),
	array('',1,',',2,',',3)
);

$tmp = $DB->implode_values(',', array(1,2,3) );
assert_equal(
	$DB->build('id IN (', $tmp, ')' ),
	"id IN (1,2,3)"
);


/** TEST flatten */
assert_equal(
	$DB->flatten( array('WHERE id=', 1, ' AND pasword=', array('PASSWORD(','aaa',')') ) ),
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

$userData = ['name'=>'John','email'=>'john@gmail.com', 'password'=>array('PASSWORD(',$password,')') ];

$id = 1;
assert_equal(
	$DB->build( $DB->build_update('users',$userData, 'id=',$id) ),
	"UPDATE \"users\" SET \"name\"='John', \"email\"='john@gmail.com', \"password\"=PASSWORD('reek') WHERE id=1"
);

echo "<b>Test success: $__TEST_COUNT</b>";	

