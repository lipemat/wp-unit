<?php
/**
 * Sensible defaults for local-config.php.
 *
 * Loaded if no local-config.php is provided.
 */

// Point to local memcache servers (Requirement of hosts like WPE).
$GLOBALS['memcached_servers'] = [ '127.0.0.1:11211' ];

define( 'ABSPATH', 'E:/SVN/starting-point/wp/' );
define( 'BOOTSTRAP', 'E:/SVN/wp-unit/includes/bootstrap.php' );
define( 'DB_HOST', 'localhost' );
define( 'DB_NAME', 'wordpress' );
define( 'DB_PASSWORD', 'hle1.qRA8W[BnR03' );
define( 'DB_USER', 'wplibs' );
define( 'DOMAIN_CURRENT_SITE', 'wp-unit.loc' );
define( 'WP_TESTS_DOMAIN', 'wp-unit.loc' );
define( 'WP_TESTS_EMAIL', 'unit-tests@test.com' );
define( 'WP_TESTS_MULTISITE', true );
define( 'WP_TESTS_SNAPSHOTS_BASE', 'Lipe\WP_Unit' );
define( 'WP_TESTS_TITLE', 'WP Unit - Self Tests' );
define( 'WP_UNIT_DIR', 'E:/SVN/wp-unit' );
define( 'WP_SITE_ROOT', 'E:/SVN/starting-point/' );
