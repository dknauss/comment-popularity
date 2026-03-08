<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

$polyfills_path = dirname( __DIR__ ) . '/inc/lib/yoast/phpunit-polyfills';
if ( file_exists( $polyfills_path . '/phpunitpolyfills-autoload.php' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills_path );
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require __DIR__ . '/../comment-popularity.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
