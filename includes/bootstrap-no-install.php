<?php

/**
 * Really only sets a const so we can load the normal
 * bootstrap without actually installing WP or losing
 * data.
 *
 * @notice Even with this set it is still recommended to never
 *         have the wp-tests-config pointed to a production db.
 *
 * @notice If your DB tables are not `INNODB` any data created by
 *         these tests will still persist.
 */
putenv( 'WP_TESTS_SKIP_INSTALL=1' );
if ( ! defined( 'WP_TESTS_SKIP_INSTALL') ) {
	define( 'WP_TESTS_SKIP_INSTALL', true );
}

// Required as of WP version 1.11.0.
if ( ! defined( 'WP_TESTS_EMAIL' ) ) {
	define( 'WP_TESTS_EMAIL', 'unit-tests@test.com' );
}
if ( ! defined( 'WP_PHP_BINARY' ) ) {
	define( 'WP_PHP_BINARY', 'php' );
}
if ( ! defined( 'WP_TESTS_TITLE' ) ) {
	define( 'WP_TESTS_TITLE', 'WordPress Unit Tests' );
}


require __DIR__ . '/bootstrap.php';
