<?php
/**
 * Installs WordPress for running the tests and loads WordPress and the test libraries
 */

/**
 * Include Composer autoloader.
 * Polyfills are required from composer.
 */
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __DIR__ ) . '/vendor/autoload.php';
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

if ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS && ! is_dir( ABSPATH ) ) {
	if ( substr( ABSPATH, -7 ) !== '/build/' ) {
		printf(
			'Error: The ABSPATH constant in the `wp-tests-config.php` file is set to a non-existent path "%s". Please verify.' . PHP_EOL,
			ABSPATH
		);
		exit( 1 );
	} else {
		echo 'Error: The PHPUnit tests should be run on the /src/ directory, not the /build/ directory.'
			. ' Please update the ABSPATH constant in your `wp-tests-config.php` file to `dirname( __FILE__ ) . \'/src/\'`'
			. ' or run `npm run build` prior to running PHPUnit.' . PHP_EOL;
		exit( 1 );
	}
}

$phpunit_version = tests_get_phpunit_version();

if ( version_compare( $phpunit_version, '5.7.21', '<' ) ) {
	printf(
		"Error: Looks like you're using PHPUnit %s. WordPress requires at least PHPUnit 5.7.21." . PHP_EOL,
		$phpunit_version
	);
	echo 'Please use the latest PHPUnit version supported for the PHP version you are running the tests on.' . PHP_EOL;
	exit( 1 );
}
unset( $phpunit_polyfills_minimum_version );

// If running core tests, check if all the required PHP extensions are loaded before running the test suite.
if ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS ) {
	$required_extensions = array(
		'gd',
	);
	$missing_extensions  = array();

	foreach ( $required_extensions as $extension ) {
		if ( ! extension_loaded( $extension ) ) {
			$missing_extensions[] = $extension;
		}
	}

	if ( $missing_extensions ) {
		printf(
			'Error: The following required PHP extensions are missing from the testing environment: %s.' . PHP_EOL,
			implode( ', ', $missing_extensions )
		);
		echo 'Please make sure they are installed and enabled.' . PHP_EOL,
		exit( 1 );
	}
}

/*
 * Load the PHPUnit Polyfills autoloader.
 *
 * The PHPUnit Polyfills are a requirement for the WP test suite.
 *
 * For running the Core tests, the Make WordPress Core handbook contains step-by-step instructions
 * on how to get up and running for a variety of supported workflows:
 * {@link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/#test-running-workflow-options}
 *
 * Plugin/theme integration tests can handle this in any of the following ways:
 * - When using a full WP install: run `composer update -W` for the WP install prior to running the tests.
 * - When using a partial WP test suite install:
 *   - Add a `yoast/phpunit-polyfills` (dev) requirement to the plugin/theme's own `composer.json` file.
 *   - And then:
 *     - Either load the PHPUnit Polyfills autoload file prior to running the WP core bootstrap file.
 *     - Or declare a `WP_TESTS_PHPUNIT_POLYFILLS_PATH` constant containing the absolute path to the
 *       root directory of the PHPUnit Polyfills installation.
 *       If the constant is used, it is strongly recommended to declare this constant in the plugin/theme's
 *       own test bootstrap file.
 *       The constant MUST be declared prior to calling this file.
 */
if ( ! class_exists( 'Yoast\PHPUnitPolyfills\Autoload' ) ) {
	// Default location of the autoloader for WP core test runs.
	$phpunit_polyfills_autoloader = dirname( dirname( dirname( __DIR__ ) ) ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
	$phpunit_polyfills_error      = false;

	// Allow for a custom installation location to be provided for plugin/theme integration tests.
	if ( defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
		$phpunit_polyfills_path = WP_TESTS_PHPUNIT_POLYFILLS_PATH;

		if ( is_string( WP_TESTS_PHPUNIT_POLYFILLS_PATH )
			&& '' !== WP_TESTS_PHPUNIT_POLYFILLS_PATH
		) {
			// Be tolerant to the path being provided including the filename.
			if ( substr( $phpunit_polyfills_path, -29 ) !== 'phpunitpolyfills-autoload.php' ) {
				$phpunit_polyfills_path = rtrim( $phpunit_polyfills_path, '/\\' );
				$phpunit_polyfills_path = $phpunit_polyfills_path . '/phpunitpolyfills-autoload.php';
			}

			$phpunit_polyfills_autoloader = $phpunit_polyfills_path;
		} else {
			$phpunit_polyfills_error = true;
		}
	}

	if ( $phpunit_polyfills_error || ! file_exists( $phpunit_polyfills_autoloader ) ) {
		echo 'Error: The PHPUnit Polyfills library is a requirement for running the WP test suite.' . PHP_EOL;
		if ( defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
			printf(
				'The PHPUnit Polyfills autoload file was not found in "%s"' . PHP_EOL,
				WP_TESTS_PHPUNIT_POLYFILLS_PATH
			);
			echo 'Please verify that the file path provided in the WP_TESTS_PHPUNIT_POLYFILLS_PATH constant is correct.' . PHP_EOL;
			echo 'The WP_TESTS_PHPUNIT_POLYFILLS_PATH constant should contain an absolute path to the root directory'
				. ' of the PHPUnit Polyfills library.' . PHP_EOL;
		} elseif ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS ) {
			echo 'You need to run `composer update -W` before running the tests.' . PHP_EOL;
			echo 'Once the dependencies are installed, you can run the tests using the Composer-installed version'
				. ' of PHPUnit or using a PHPUnit phar file, but the dependencies do need to be installed'
				. ' whichever way the tests are run.' . PHP_EOL;
		} else {
			echo 'If you are trying to run plugin/theme integration tests, make sure the PHPUnit Polyfills library'
				. ' (https://github.com/Yoast/PHPUnit-Polyfills) is available and either load the autoload file'
				. ' of this library in your own test bootstrap before calling the WP Core test bootstrap file;'
				. ' or set the absolute path to the PHPUnit Polyfills library in a "WP_TESTS_PHPUNIT_POLYFILLS_PATH"'
				. ' constant to allow the WP Core bootstrap to load the Polyfills.' . PHP_EOL . PHP_EOL;
			echo 'If you are trying to run the WP Core tests, make sure to set the "WP_RUN_CORE_TESTS" constant'
				. ' to 1 and run `composer update -W` before running the tests.' . PHP_EOL;
			echo 'Once the dependencies are installed, you can run the tests using the Composer-installed'
				. ' version of PHPUnit or using a PHPUnit phar file, but the dependencies do need to be'
				. ' installed whichever way the tests are run.' . PHP_EOL;
		}
		exit( 1 );
	}

	require_once $phpunit_polyfills_autoloader;
}
unset( $phpunit_polyfills_autoloader, $phpunit_polyfills_error, $phpunit_polyfills_path );

/*
 * Minimum version of the PHPUnit Polyfills package as declared in `composer.json`.
 * Only needs updating when new polyfill features start being used in the test suite.
 */
$phpunit_polyfills_minimum_version = '1.0.1';
if ( class_exists( '\Yoast\PHPUnitPolyfills\Autoload' )
	&& ( defined( '\Yoast\PHPUnitPolyfills\Autoload::VERSION' ) === false
	|| version_compare( Yoast\PHPUnitPolyfills\Autoload::VERSION, $phpunit_polyfills_minimum_version, '<' ) )
) {
	printf(
		'Error: Version mismatch detected for the PHPUnit Polyfills.'
		. ' Please ensure that PHPUnit Polyfills %s or higher is loaded. Found version: %s' . PHP_EOL,
		$phpunit_polyfills_minimum_version,
		defined( '\Yoast\PHPUnitPolyfills\Autoload::VERSION' ) ? Yoast\PHPUnitPolyfills\Autoload::VERSION : '1.0.0 or lower'
	);
	if ( defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
		printf(
			'Please ensure that the PHPUnit Polyfill installation in "%s" is updated to version %s or higher.' . PHP_EOL,
			WP_TESTS_PHPUNIT_POLYFILLS_PATH,
			$phpunit_polyfills_minimum_version
		);
	} elseif ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS ) {
		echo 'Please run `composer update -W` to install the latest version.' . PHP_EOL;
	}
	exit( 1 );
}

$required_constants = [
	'WP_TESTS_DOMAIN',
	'WP_TESTS_EMAIL',
	'WP_TESTS_TITLE',
	'WP_PHP_BINARY',
];
$missing_constants = [];

foreach ( $required_constants as $constant ) {
	if ( ! defined( $constant ) ) {
		$missing_constants[] = $constant;
	}
}

if ( $missing_constants ) {
	printf(
		'Error: The following required constants are not defined: %s.' . PHP_EOL,
		implode( ', ', $missing_constants )
	);
	echo 'Please check out `wp-tests-config-sample.php` for an example.' . PHP_EOL,
	exit( 1 );
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
	echo 'Running as single site...' . PHP_EOL;
}


$GLOBALS['_wp_die_disabled'] = false;
// Allow tests to override wp_die().
tests_add_filter( 'wp_die_handler', '_wp_die_handler_filter' );
// Use the Spy REST Server instead of default.
tests_add_filter( 'wp_rest_server_class', '_wp_rest_server_class_filter' );
// Prevent updating translations asynchronously.
tests_add_filter( 'async_update_translation', '__return_false' );
// Disable background updates.
tests_add_filter( 'automatic_updater_disabled', '__return_true' );

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

// Load class aliases for compatibility with PHPUnit 6+.
if ( version_compare( tests_get_phpunit_version(), '6.0', '>=' ) ) {
	require __DIR__ . '/phpunit6/compat.php';
}

require __DIR__ . '/phpunit-adapter-testcase.php';
require __DIR__ . '/abstract-testcase.php';
require __DIR__ . '/testcase-base.php';
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

	public function __construct( $argv ) {
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
