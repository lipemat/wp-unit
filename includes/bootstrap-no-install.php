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

// New version
putenv( 'WP_TESTS_SKIP_INSTALL=1' );
if ( ! defined( 'WP_TESTS_SKIP_INSTALL') ) {
	define( 'WP_TESTS_SKIP_INSTALL', true );
}

require dirname( __FILE__ ) . '/bootstrap.php';
