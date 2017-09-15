<?php
require_once "simple_test.php";

require_once "../src/Prepared.php";
require_once "../src/Query.php";
require_once "../src/HipsterSql.php";
require_once "../src/hipster_sql.php";

$DB = new org\hrg\php_hipster_sql\HipsterSql();

function hip_get_db(){ global $DB; return $DB;} // required for procedural style

assert_equal(
	new org\hrg\php_hipster_sql\Prepared('select id,name from users where id=?',1),
	array('sql'=>'select id,name from users where id=?','args'=>array(1))
);

assert_equal(
	hip_prepare('select id,name from users where id=?',1),
	array('sql'=>'select id,name from users where id=?','args'=>array(1))
);

assert_equal(
	hip_build('select id,name from users where id=',1),
	'select id,name from users where id=1'
);

assert_equal(
	hip_build('select id,','name',' from users where id=',1),
	'select id,\'name\' from users where id=1'
);

assert_equal(
	hip_build('select id,','name',' from users where birthday=','1990\\03\\05'),
	'select id,\'name\' from users where birthday=\'1990\\\\03\\\\05\''
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

$array = array(hip_q('id=',1),hip_q('date>','2016-01-01'));
assert_equal(
	hip_build( hip_implode('WHERE ',' AND ', $array,' AND 1=1') ),
	'WHERE id=1 AND date>\'2016-01-01\' AND 1=1'
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

$array = array(1,2,3);
assert_equal(
	hip_build( hip_implode_values('id IN (',',', $array, ')') ),
	"id IN (1,2,3)"
);

/** TEST flatten */
assert_equal(
	hip_q( 'WHERE id=', 1, ' AND pasword=', hip_q('PASSWORD(','aaa',')') ) ->flatten()->get_query_array(),
	array('WHERE id=', 1, ' AND pasword=PASSWORD(','aaa',')') 
);

/** TEST prepare */
assert_equal(
	hip_q( array('WHERE id=', 1 ) )->prepare(),
	array('sql'=>'WHERE id=?','args'=>array(1))
);

assert_equal(
	hip_q( array('WHERE id=', 1, ' AND is_deleted=',0 ) )->prepare(),
	array('sql'=>'WHERE id=? AND is_deleted=?','args'=>array(1,0))
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

/*** TEST complicated append versions combined query parts with query objects between **/
$q1 = hip_q(" and user_id=",1);
$q2 = hip_q(" and is_deleted=",0);

assert_equal(
	hip_build('select * from users WHERE 1=1', $q1, ' ORDER BY name'),
	"select * from users WHERE 1=1 and user_id=1 ORDER BY name"
);

assert_equal(
	hip_build(hip_q('select * from users WHERE 1=1', $q1, ' ORDER BY name')->flatten()),
	"select * from users WHERE 1=1 and user_id=1 ORDER BY name"
);

assert_equal(
	hip_build(hip_q()->append('select * from users WHERE 1=1', $q1, ' ORDER BY name')),
	"select * from users WHERE 1=1 and user_id=1 ORDER BY name"
);

assert_equal(
	hip_build('select * from users WHERE 1=1', $q1, $q2, ' ORDER BY name'),
	"select * from users WHERE 1=1 and user_id=1 and is_deleted=0 ORDER BY name"
);

assert_equal(
	hip_build(hip_q('select * from users WHERE 1=1', $q1, $q2, ' ORDER BY name')->flatten()),
	"select * from users WHERE 1=1 and user_id=1 and is_deleted=0 ORDER BY name"
);

assert_equal(
	hip_build(hip_q()->append('select * from users WHERE 1=1', $q1, $q2, ' ORDER BY name')),
	"select * from users WHERE 1=1 and user_id=1 and is_deleted=0 ORDER BY name"
);


echo "<b>Test success: $__TEST_COUNT</b>";	

