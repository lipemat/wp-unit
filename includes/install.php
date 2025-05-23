<?php
/**
 * Installs WordPress for the unit-tests.
 */

error_reporting( E_ALL & ~E_DEPRECATED );

$config_file_path = $argv[1];
$multisite = in_array( 'run_ms_tests', $argv, true );

define( 'WP_INSTALLING', true );

/*
 * Cron tries to make an HTTP request to the site, which always fails,
 * because tests are run in CLI mode only.
 */
define( 'DISABLE_WP_CRON', true );

require_once $config_file_path;
require_once __DIR__ . '/functions.php';

if ( ! defined( 'WP_TESTS_TABLE_PREFIX' ) && isset( $table_prefix ) ) {
	define( 'WP_TESTS_TABLE_PREFIX', $table_prefix );
} else {
	$table_prefix = WP_TESTS_TABLE_PREFIX;
}

// Set the theme to our special empty theme, to avoid interference from the current Twenty* theme.
if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	define( 'WP_DEFAULT_THEME', 'default' );
}

tests_reset__SERVER();

$PHP_SELF = '/index.php';
$GLOBALS['PHP_SELF'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

tests_add_filter( 'wp_die_handler', '_wp_die_handler_filter_exit' );

require_once ABSPATH . 'wp-settings.php';

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( file_exists( ABSPATH . 'wp-includes/class-wpdb.php' ) ) {
	require_once ABSPATH . 'wp-includes/class-wpdb.php';
} else {
	require_once ABSPATH . 'wp-includes/wp-db.php';
}

// Override the PHPMailer.
global $phpmailer;
require_once __DIR__ . '/mock-mailer.php';
$phpmailer = new MockPHPMailer();

if ( version_compare( $wpdb->db_version(), '5.5.3', '>=' ) ) {
	$wpdb->query( 'SET default_storage_engine = InnoDB' );
}
$wpdb->select( DB_NAME, $wpdb->dbh );

echo 'Installing...' . PHP_EOL;

$wpdb->query( 'SET foreign_key_checks = 0' );
foreach ( $wpdb->tables() as $table => $prefixed_table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS $prefixed_table" );
}

foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS $prefixed_table" );

	// We need to create references to ms global tables.
	if ( $multisite ) {
		$wpdb->$table = $prefixed_table;
	}
}
$wpdb->query( 'SET foreign_key_checks = 1' );

// Prefill a permalink structure so that WP doesn't try to determine one itself.
add_action( 'populate_options', '_set_default_permalink_structure_for_tests' );

$admin_user = defined( 'WP_TESTS_USER' ) ? WP_TESTS_USER : 'wp-unit-admin';
wp_install( WP_TESTS_TITLE, $admin_user, WP_TESTS_EMAIL, true, null, 'password' );

// Delete dummy permalink structure, as prefilled above.
if ( ! is_multisite() ) {
	delete_option( 'permalink_structure' );
}
remove_action( 'populate_options', '_set_default_permalink_structure_for_tests' );

if ( $multisite ) {
	echo 'Installing network...' . PHP_EOL;

	define( 'WP_INSTALLING_NETWORK', true );

	$title = WP_TESTS_TITLE . ' Network';
	$subdomain_install = false;

	install_network();
	$error = populate_network( 1, WP_TESTS_DOMAIN, WP_TESTS_EMAIL, $title, '/', $subdomain_install );

	if ( is_wp_error( $error ) ) {
		wp_die( $error );
	}

	$wp_rewrite->set_permalink_structure( '' );
}
