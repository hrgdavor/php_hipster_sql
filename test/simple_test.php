<?php
$__TEST_COUNT = 0;

function assert_equal($real, $expected){ global $__TEST_COUNT;
	$json_real = json_encode($real);
	$json_expected = json_encode($expected);
	if( $json_real != $json_expected){
		echo "<pre>Test failed:\n Value:\n$json_real\nExpected:\n$json_expected\n\n";
		print_r(debug_backtrace());
		die();
	}
	$__TEST_COUNT++;
}