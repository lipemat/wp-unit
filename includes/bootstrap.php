<?php
/**
 * Installs WordPress for running the tests and loads WordPress and the test libraries
 */

/**
 * Compatibility with PHPUnit 6+
 */
if ( class_exists( 'PHPUnit\Runner\Version' ) ) {
	require_once __DIR__ . '/phpunit6/compat.php';
}

if ( defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	$config_file_path = WP_TESTS_CONFIG_FILE_PATH;
} else {
	$config_file_path = dirname( __DIR__ );
	if ( ! file_exists( $config_file_path . '/wp-tests-config.php' ) ) {
		// Support the config file from the root of the develop repository.
		if ( basename( $config_file_path ) === 'phpunit' && basename( dirname( $config_file_path ) ) === 'tests' ) {
			$config_file_path = dirname( dirname( $config_file_path ) );
		}
	}
	$config_file_path .= '/wp-tests-config.php';
}

/*
 * Globalize some WordPress variables, because PHPUnit loads this file inside a function.
 * See: https://github.com/sebastianbergmann/phpunit/issues/325
 */
global $wpdb, $current_site, $current_blog, $wp_rewrite, $shortcode_tags, $wp, $phpmailer, $wp_theme_directories, $wp_version;

/**
 * If we are requiring a config file from a constant or a develop
 * directory.
 *
 * There is a good chance we have already included our config in our project's bootstrap.php
 *
 */
if ( is_readable( $config_file_path ) ) {
	require_once $config_file_path;
}
require_once __DIR__ . '/functions.php';

$phpunit_version = tests_get_phpunit_version();

if ( version_compare( $phpunit_version, '5.4', '<' ) || version_compare( $phpunit_version, '8.0', '>=' ) ) {
	printf(
		"Error: Looks like you're using PHPUnit %s. WordPress requires at least PHPUnit 5.4 and is currently only compatible with PHPUnit up to 7.x.\n",
		$phpunit_version
	);
	echo "Please use the latest PHPUnit version from the 7.x branch.\n";
	exit( 1 );
}

if ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS && ! is_dir( ABSPATH ) ) {
	echo "Error: The /build/ directory is missing! Please run `npm run build` prior to running PHPUnit.\n";
	exit( 1 );
}

$required_constants = [
	'WP_TESTS_DOMAIN',
	'WP_TESTS_EMAIL',
	'WP_TESTS_TITLE',
	'WP_PHP_BINARY',
];

foreach ( $required_constants as $constant ) {
	if ( ! defined( $constant ) ) {
		printf(
			"Error: The required %s constant is not defined. Check out `wp-tests-config-sample.php` for an example.\n",
			$constant
		);
		exit( 1 );
	}
}

tests_reset__SERVER();

if ( ! defined( 'WP_TESTS_TABLE_PREFIX' ) && isset( $table_prefix ) ) {
	define( 'WP_TESTS_TABLE_PREFIX', $table_prefix );
} else {
	$table_prefix = WP_TESTS_TABLE_PREFIX;
}
define( 'DIR_TESTDATA', __DIR__ . '/../data' );
define( 'DIR_TESTROOT', realpath( dirname( __DIR__ ) ) );

if ( ! defined( 'WP_LANG_DIR' ) ) {
	define( 'WP_LANG_DIR', DIR_TESTDATA . '/languages' );
}

if ( ! defined( 'WP_TESTS_FORCE_KNOWN_BUGS' ) ) {
	define( 'WP_TESTS_FORCE_KNOWN_BUGS', false );
}

/*
 * Cron tries to make an HTTP request to the site, which always fails,
 * because tests are run in CLI mode only.
 */
define( 'DISABLE_WP_CRON', true );

if( !defined( 'WP_MEMORY_LIMIT' ) ) {
	define( 'WP_MEMORY_LIMIT', -1 );
}
define( 'WP_MAX_MEMORY_LIMIT', WP_MEMORY_LIMIT );

define( 'REST_TESTS_IMPOSSIBLY_HIGH_NUMBER', 99999999 );

$PHP_SELF            = '/index.php';
$GLOBALS['PHP_SELF'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Should we run in multisite mode?
$multisite = ( '1' === getenv( 'WP_MULTISITE' ) );
$multisite = $multisite || ( defined( 'WP_TESTS_MULTISITE' ) && WP_TESTS_MULTISITE );
$multisite = $multisite || ( defined( 'MULTISITE' ) && MULTISITE );

// Override the PHPMailer
if( !defined( 'WP_TESTS_SEND_MAIL' ) || !WP_TESTS_SEND_MAIL ){
	require_once __DIR__ . '/mock-mailer.php';
	$phpmailer = new MockPHPMailer();
}

if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	define( 'WP_DEFAULT_THEME', 'default' );
}
$wp_theme_directories = array();

if ( file_exists( DIR_TESTDATA . '/themedir1' ) ) {
	$wp_theme_directories[] = DIR_TESTDATA . '/themedir1';
}

if ( ! tests_skip_install() ) {
	$core_tests = ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS ) ? 'run_core_tests' : 'no_core_tests';
	$ms_tests   = $multisite ? 'run_ms_tests' : 'no_ms_tests';

	system( WP_PHP_BINARY . ' ' . escapeshellarg( __DIR__ . '/install.php' ) . ' ' . escapeshellarg( $config_file_path ) . ' ' . $ms_tests . ' ' . $core_tests, $retval );
	if ( 0 !== $retval ) {
		exit( $retval );
	}
}

if ( $multisite ) {
	echo 'Running as multisite...' . PHP_EOL;
	defined( 'MULTISITE' ) or define( 'MULTISITE', true );
	defined( 'SUBDOMAIN_INSTALL' ) or define( 'SUBDOMAIN_INSTALL', false );
	$GLOBALS['base'] = '/';
} else {
	echo 'Running as single site... To run multisite, use -c tests/phpunit/multisite.xml' . PHP_EOL;
}


$GLOBALS['_wp_die_disabled'] = false;
// Allow tests to override wp_die().
tests_add_filter( 'wp_die_handler', '_wp_die_handler_filter' );
// Use the Spy REST Server instead of default.
tests_add_filter( 'wp_rest_server_class', '_wp_rest_server_class_filter' );

// Preset WordPress options defined in bootstrap file.
// Used to activate themes, plugins, as well as other settings.
if ( isset( $GLOBALS['wp_tests_options'] ) ) {
	function wp_tests_options( $value ) {
		$key = substr( current_filter(), strlen( 'pre_option_' ) );
		return $GLOBALS['wp_tests_options'][ $key ];
	}

	foreach ( array_keys( $GLOBALS['wp_tests_options'] ) as $key ) {
		tests_add_filter( 'pre_option_' . $key, 'wp_tests_options' );
	}

	function wp_tests_network_options( $value ) {
		$key = substr( current_filter(), strlen( 'pre_site_option_' ) );
		return $GLOBALS['wp_tests_options'][$key];
	}

	//filter the site options with our test options
	foreach ( array_keys( $GLOBALS['wp_tests_options'] ) as $key ) {
		tests_add_filter( 'pre_site_option_'.$key, 'wp_tests_network_options' );
	}
}

// Preset Filters defined in bootstrap file
// Use to filter items before test classes are loaded
if ( isset( $GLOBALS[ 'wp_tests_filters' ] ) ){
	foreach( (array)$GLOBALS[ 'wp_tests_filters' ] as $filter => $callback ){
		tests_add_filter( $filter, $callback );
	}
}



// Load WordPress.
require_once ABSPATH . '/wp-settings.php';
require_once dirname( __FILE__ ) . '/template-tags/cron.php';


// Switch to the blog we have defined in the wp-tests-config
if( $multisite ){
	if( defined( 'BLOG_ID_CURRENT_SITE' ) ){
		switch_to_blog( BLOG_ID_CURRENT_SITE );
	}
}
// unset this later so we can use it after WP loads
unset( $multisite );



// Delete any default posts & related data.
if ( ! tests_skip_install() ) {
	_delete_all_posts();
}

if ( version_compare( tests_get_phpunit_version(), '7.0', '>=' ) ) {
	require __DIR__ . '/phpunit7/testcase.php';
} else {
	require __DIR__ . '/testcase.php';
}

require __DIR__ . '/testcase-http-remote-post.php';
require __DIR__ . '/testcase-rest-api.php';
require __DIR__ . '/testcase-rest-controller.php';
require __DIR__ . '/testcase-rest-post-type-controller.php';
require __DIR__ . '/testcase-xmlrpc.php';
require __DIR__ . '/testcase-ajax.php';
require __DIR__ . '/testcase-canonical.php';
require __DIR__ . '/testcase-xml.php';
require __DIR__ . '/exceptions.php';
require __DIR__ . '/utils.php';
require __DIR__ . '/spy-rest-server.php';
require __DIR__ . '/class-wp-rest-test-search-handler.php';
require __DIR__ . '/class-wp-rest-test-configurable-controller.php';
require __DIR__ . '/class-wp-fake-block-type.php';
require __DIR__ . '/class-wp-sitemaps-test-provider.php';
require __DIR__ . '/class-wp-sitemaps-empty-test-provider.php';
require __DIR__ . '/class-wp-sitemaps-large-test-provider.php';

/**
 * A class to handle additional command line arguments passed to the script.
 *
 * If it is determined that phpunit was called with a --group that corresponds
 * to an @ticket annotation (such as `phpunit --group 12345` for bugs marked
 * as #WP12345), then it is assumed that known bugs should not be skipped.
 *
 * If WP_TESTS_FORCE_KNOWN_BUGS is already set in wp-tests-config.php, then
 * how you call phpunit has no effect.
 */
class WP_PHPUnit_Util_Getopt {

	function __construct( $argv ) {
		$skipped_groups = array(
			'ajax'          => true,
			'ms-files'      => true,
			'external-http' => true,
		);

		while ( current( $argv ) ) {
			$option = current( $argv );
			$value  = next( $argv );

			switch ( $option ) {
				case '--exclude-group':
					foreach ( $skipped_groups as $group_name => $skipped ) {
						$skipped_groups[ $group_name ] = false;
					}
					continue 2;
				case '--group':
					$groups = explode( ',', $value );
					foreach ( $groups as $group ) {
						if ( is_numeric( $group ) || preg_match( '/^(UT|Plugin)\d+$/', $group ) ) {
							WP_UnitTestCase::forceTicket( $group );
						}
					}

					foreach ( $skipped_groups as $group_name => $skipped ) {
						if ( in_array( $group_name, $groups, true ) ) {
							$skipped_groups[ $group_name ] = false;
						}
					}
					continue 2;
			}
		}

		$skipped_groups = array_filter( $skipped_groups );
		foreach ( $skipped_groups as $group_name => $skipped ) {
			echo sprintf( 'Not running %1$s tests. To execute these, use --group %1$s.', $group_name ) . PHP_EOL;
		}

		if ( ! isset( $skipped_groups['external-http'] ) ) {
			echo PHP_EOL;
			echo 'External HTTP skipped tests can be caused by timeouts.' . PHP_EOL;
			echo 'If this changeset includes changes to HTTP, make sure there are no timeouts.' . PHP_EOL;
			echo PHP_EOL;
		}
	}

}
new WP_PHPUnit_Util_Getopt( $_SERVER['argv'] );
