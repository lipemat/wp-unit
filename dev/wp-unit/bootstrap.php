<?php
/**
 * @version 2.0.0
 *
 */

$GLOBALS['wp_tests_options']['permalink_structure'] = '%postname%/';

require __DIR__ . '/helpers.php';
require __DIR__ . '/wp-tests-config.php';
require_once dirname( __DIR__, 2 ) . '/includes/functions.php';

tests_add_filter( 'wp-unit/set_up', function() {
	$GLOBALS['bootstrap/testing'] = true;
} );

tests_add_filter( 'wp-unit/reset-container', function() {
	$GLOBALS['bootstrap/testing'] = false;
} );

require BOOTSTRAP;
