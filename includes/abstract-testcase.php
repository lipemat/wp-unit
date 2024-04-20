<?php

use Lipe\WP_Unit\Framework\Deprecated_TestCase_Base;
use Lipe\WP_Unit\Helpers\Deprecated_Usage;
use Lipe\WP_Unit\Helpers\Doing_It_Wrong;
use Lipe\WP_Unit\Helpers\Global_Hooks;
use Lipe\WP_Unit\Helpers\Hook_State;
use Lipe\WP_Unit\Helpers\Setup_Teardown_State;
use Lipe\WP_Unit\Helpers\Snapshots;
use Lipe\WP_Unit\Traits\Helper_Access;

require_once __DIR__ . '/factory.php';
require_once __DIR__ . '/trac.php';

/**
 * Defines a basic fixture to run multiple tests.
 *
 * Resets the state of the WordPress installation before and after every test.
 *
 * Includes utility functions and assertions useful for testing WordPress.
 *
 * All WordPress unit tests should inherit from this class.
 */
abstract class WP_UnitTestCase_Base extends PHPUnit_Adapter_TestCase {
	use Deprecated_TestCase_Base;

	/**
	 * @var ?Hook_State
	 */
	protected $hook_state;

	/**
	 * @var ?Deprecated_Usage
	 */
	protected $deprecated_usage;

	/**
	 * @var ?Doing_It_Wrong
	 */
	protected $doing_it_wrong;

	protected static $forced_tickets   = array();

	protected static $ignore_files;


	/**
	 * Fetches the factory object for generating WordPress fixtures.
	 *
	 * @return WP_UnitTest_Factory The fixture factory.
	 */
	protected static function factory() {
		static $factory = null;
		if ( ! $factory ) {
			$factory = new WP_UnitTest_Factory();
		}
		return $factory;
	}

	/**
	 * Runs the routine before setting up all tests.
	 */
	public static function set_up_before_class() {
		global $wpdb;

		parent::set_up_before_class();

		$wpdb->suppress_errors = false;
		$wpdb->show_errors     = true;
		$wpdb->db_connect();
		ini_set( 'display_errors', '1' );

		$class = get_called_class();

		if ( method_exists( $class, 'wpSetUpBeforeClass' ) ) {
			call_user_func( array( $class, 'wpSetUpBeforeClass' ), static::factory() );
		}

		self::commit_transaction();
		Setup_Teardown_State::set_up_before_class( $class );
	}

	/**
	 * Runs the routine after all tests have been run.
	 */
	public static function tear_down_after_class() {
		$class = get_called_class();

		if ( method_exists( $class, 'wpTearDownAfterClass' ) ) {
			call_user_func( [ $class, 'wpTearDownAfterClass' ] );
		}

		if ( ! tests_skip_install() ) {
			_delete_all_data();
		}
		self::flush_cache();

		self::commit_transaction();

		Global_Hooks::instance()->restore_globals();
		Setup_Teardown_State::tear_down_after_class( $class );

		parent::tear_down_after_class();
	}

	/**
	 * Runs the routine before each test is executed.
	 */
	public function set_up() {
		set_time_limit( 0 );

		$this->factory = static::factory();

		if ( ! self::$ignore_files ) {
			self::$ignore_files = $this->scan_user_uploads();
		}

		$this->_backup_hooks();

		// Load the helpers into the stack.
		$this->deprecated_usage = Deprecated_Usage::factory( $this );
		$this->doing_it_wrong = Doing_It_Wrong::factory( $this );

		global $wp_rewrite;

		$this->clean_up_global_scope();

		/*
		 * When running core tests, ensure that post types and taxonomies
		 * are reset for each test. We skip this step for non-core tests,
		 * given the large number of plugins that register post types and
		 * taxonomies at 'init'.
		 */
		if ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS ) {
			$this->reset_post_types();
			$this->reset_taxonomies();
			$this->reset_post_statuses();

			if ( $wp_rewrite->permalink_structure ) {
				$this->set_permalink_structure( '' );
			}
		}
		$this->reset__SERVER();

		$this->start_transaction();
		$this->_fill_expected_deprecated();
		add_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ) );

		Setup_Teardown_State::set_up();
	}

	/**
	 * After a test method runs, resets any state in WordPress the test method might have changed.
	 */
	public function tear_down() {
		global $wpdb, $wp_the_query, $wp_query, $wp, $wp_unit_torn_down;
		$wpdb->query( 'ROLLBACK' );
		if ( is_multisite() ) {
			while ( ms_is_switched() ) {
				restore_current_blog();
			}
		}

		// Reset query, main query, and WP globals similar to wp-settings.php.
		$wp_the_query = new WP_Query();
		$wp_query     = $wp_the_query;
		$wp           = new WP();

		// Reset globals related to the post loop and `setup_postdata()`.
		$post_globals = [ 'post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'comment' ];
		foreach ( $post_globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		/*
		 * Reset globals related to current screen to provide a consistent global starting state
		 * for tests that interact with admin screens. Replaces the need for individual tests
		 * to invoke `set_current_screen( 'front' )` (or an alternative implementation) as a reset.
		 *
		 * The globals are from `WP_Screen::set_current_screen()`.
		 *
		 * Why not invoke `set_current_screen( 'front' )`?
		 * Performance (faster test runs with less memory usage). How so? For each test,
		 * it saves creating an instance of WP_Screen, making two method calls,
		 * and firing of the `current_screen` action.
		 */
		$current_screen_globals = array( 'current_screen', 'taxnow', 'typenow' );
		foreach ( $current_screen_globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		// Reset comment globals.
		$comment_globals = array( 'comment_alt', 'comment_depth', 'comment_thread_alt' );
		foreach ( $comment_globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		/*
		 * Reset $wp_sitemap global so that sitemap-related dynamic $wp->public_query_vars
		 * are added when the next test runs.
		 */
		$GLOBALS['wp_sitemaps'] = null;

		// Reset template globals.
		$GLOBALS['wp_stylesheet_path'] = null;
		$GLOBALS['wp_template_path']   = null;

		$this->unregister_all_meta_keys();
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		remove_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ) );

		// Reset a project container if available.
		if ( function_exists( 'tests_reset_container' ) ) {
			tests_reset_container();
		}
		// Reset the PHP mailer.
		reset_phpmailer_instance();

		$this->_restore_hooks();
		wp_set_current_user( 0 );

		$this->reset_lazyload_queue();

		Setup_Teardown_State::tear_down();
	}

	/**
	 * Cleans the global scope (e.g `$_GET` and `$_POST`).
	 */
	public function clean_up_global_scope() {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		self::flush_cache();
	}

	/**
	 * Allows tests to be skipped on some automated runs.
	 *
	 * For test runs on GitHub Actions for something other than trunk,
	 * we want to skip tests that only need to run for trunk.
	 */
	public function skipOnAutomatedBranches() {
		// https://docs.github.com/en/actions/learn-github-actions/environment-variables#default-environment-variables
		$github_event_name = getenv( 'GITHUB_EVENT_NAME' );
		$github_ref        = getenv( 'GITHUB_REF' );

		if ( $github_event_name ) {
			// We're on GitHub Actions.
			$skipped = array( 'pull_request', 'pull_request_target' );

			if ( in_array( $github_event_name, $skipped, true ) || 'refs/heads/trunk' !== $github_ref ) {
				$this->markTestSkipped( 'For automated test runs, this test is only run on trunk' );
			}
		}
	}

	/**
	 * Allows tests to be skipped when Multisite is not in use.
	 *
	 * Use in conjunction with the ms-required group.
	 */
	public function skipWithoutMultisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Test only runs on Multisite' );
		}
	}

	/**
	 * Allows tests to be skipped when Multisite is in use.
	 *
	 * Use in conjunction with the ms-excluded group.
	 */
	public function skipWithMultisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Test does not run on Multisite' );
		}
	}

	/**
	 * Allows tests to be skipped if the HTTP request times out.
	 *
	 * @param array|WP_Error $response HTTP response.
	 */
	public function skipTestOnTimeout( $response ) {
		if ( ! is_wp_error( $response ) ) {
			return;
		}
		if ( 'connect() timed out!' === $response->get_error_message() ) {
			$this->markTestSkipped( 'HTTP timeout' );
		}

		if ( false !== strpos( $response->get_error_message(), 'timed out after' ) ) {
			$this->markTestSkipped( 'HTTP timeout' );
		}

		if ( 0 === strpos( $response->get_error_message(), 'stream_socket_client(): unable to connect to tcp://s.w.org:80' ) ) {
			$this->markTestSkipped( 'HTTP timeout' );
		}
	}

	/**
	 * Reset the lazy load meta queue.
	 */
	protected function reset_lazyload_queue() {
		$lazyloader = wp_metadata_lazyloader();
		$lazyloader->reset_queue( 'term' );
		$lazyloader->reset_queue( 'comment' );
		$lazyloader->reset_queue( 'blog' );
	}

	/**
	 * Unregisters existing post types and register defaults.
	 *
	 * Run before each test in order to clean up the global scope, in case
	 * a test forgets to unregister a post type on its own, or fails before
	 * it has a chance to do so.
	 */
	protected function reset_post_types() {
		foreach ( get_post_types( array(), 'objects' ) as $pt ) {
			if ( empty( $pt->tests_no_auto_unregister ) ) {
				_unregister_post_type( $pt->name );
			}
		}
		create_initial_post_types();
	}

	/**
	 * Unregisters existing taxonomies and register defaults.
	 *
	 * Run before each test in order to clean up the global scope, in case
	 * a test forgets to unregister a taxonomy on its own, or fails before
	 * it has a chance to do so.
	 */
	protected function reset_taxonomies() {
		foreach ( get_taxonomies() as $tax ) {
			_unregister_taxonomy( $tax );
		}
		create_initial_taxonomies();
	}

	/**
	 * Unregisters non-built-in post statuses.
	 */
	protected function reset_post_statuses() {
		foreach ( get_post_stati( array( '_builtin' => false ) ) as $post_status ) {
			_unregister_post_status( $post_status );
		}
	}

	/**
	 * Resets `$_SERVER` variables
	 */
	protected function reset__SERVER() {
		tests_reset__SERVER();
	}

	/**
	 * Saves the hook-related globals, so they can be restored later.
	 *
	 * Stores $wp_filter, $wp_actions, $wp_filters, $wp_meta_keys, $wp_registered_settings and $wp_current_filter
	 * on a class variable, so they can be restored on tear_down() using _restore_hooks().
	 *
	 * @note This method differs from WP Core as it will also back up the
	 *       `wp_meta_keys` and `wp_register_settings` globals.
	 *
	 * @global array $wp_filter
	 * @global array $wp_actions
	 * @global array $wp_filters
	 * @global array $wp_current_filter
	 * @global array $wp_meta_keys
	 */
	protected function _backup_hooks() {
		$this->hook_state = Hook_State::factory();
		if ( ! self::$hooks_saved ) {
			self::$hooks_saved = $this->hook_state->get_legacy_hooks();
		}
	}

	/**
	 * Restores the hook-related globals to their state at set_up()
	 * so that future tests aren't affected by hooks set during this last test.
	 *
	 * @note This method differs from WP Core as it will also restore the
	 *       `wp_meta_keys` and `wp_registered_settings` globals.
	 *
	 * @global array $wp_actions
	 * @global array $wp_current_filter
	 * @global array $wp_filter
	 * @global array $wp_meta_keys
	 */
	protected function _restore_hooks() {
		if ( $this->hook_state instanceof Hook_State ) {
			Global_Hooks::instance()->restore_hooks( $this->hook_state );
		}
	}

	/**
	 * Flushes the WordPress object cache.
	 */
	public static function flush_cache() {
		global $wp_object_cache;

		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' ) ) {
			wp_cache_flush_runtime();
		}

		if ( is_object( $wp_object_cache ) && method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset();
		}

		wp_cache_flush();

		wp_cache_add_global_groups(
			array(
				'blog-details',
				'blog-id-cache',
				'blog-lookup',
				'blog_meta',
				'global-posts',
				'networks',
				'network-queries',
				'sites',
				'site-details',
				'site-options',
				'site-queries',
				'site-transient',
				'theme_files',
				'rss',
				'users',
				'user-queries',
				'user_meta',
				'useremail',
				'userlogins',
				'userslugs',
			)
		);

		wp_cache_add_non_persistent_groups( array( 'counts', 'plugins', 'theme_json' ) );
	}

	/**
	 * Cleans up any registered meta keys.
	 *
	 * @notice When not running core tests, the meta keys are restored via
	 *         `$this->_restore_hooks` so this method does nothing.
	 *
	 * @see WP_UnitTestCase_Base::_restore_hooks()
	 *
	 * @since 5.1.0
	 *
	 * @global array $wp_meta_keys
	 */
	public function unregister_all_meta_keys() {
		global $wp_meta_keys;
		if ( ! is_array( $wp_meta_keys ) ) {
			return;
		}
		foreach ( $wp_meta_keys as $object_type => $type_keys ) {
			foreach ( $type_keys as $object_subtype => $subtype_keys ) {
				foreach ( $subtype_keys as $key => $value ) {
					unregister_meta_key( $object_type, $key, $object_subtype );
				}
			}
		}
	}

	/**
	 * Starts a database transaction.
	 */
	public function start_transaction() {
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'START TRANSACTION;' );
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		add_filter( 'query', array( __CLASS__, '_prevent_premature_commit' ) );
		add_filter( 'query', array( __CLASS__, '_prevent_second_transaction' ) );
	}

	/**
	 * Commits the queries in a transaction.
	 *
	 * @since 4.1.0
	 */
	public static function commit_transaction() {
		global $wpdb;
		remove_filter( 'query', array( __CLASS__, '_prevent_premature_commit' ) );
		$wpdb->query( 'COMMIT;' );
		add_filter( 'query', array( __CLASS__, '_prevent_premature_commit' ) );
	}

	/**
	 * Replaces the `CREATE TABLE` statement with a `CREATE TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 * @return string The altered query.
	 */
	public function _create_temporary_tables( $query ) {
		if ( 0 === strpos( trim( $query ), 'CREATE TABLE' ) ) {
			return substr_replace( trim( $query ), 'CREATE TEMPORARY TABLE', 0, 12 );
		}
		return $query;
	}

	/**
	 * Replaces the `DROP TABLE` statement with a `DROP TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 * @return string The altered query.
	 */
	public function _drop_temporary_tables( $query ) {
		if ( 0 === strpos( trim( $query ), 'DROP TABLE' ) ) {
			return substr_replace( trim( $query ), 'DROP TEMPORARY TABLE', 0, 10 );
		}
		return $query;
	}

	static function _prevent_premature_commit( $query ) {
		if( 'COMMIT' === substr( trim( $query ), 0, 6 ) ) {
			return 'SELECT "Bypassed COMMIT transaction _prevent_premature_commit"';
		}
		return $query;
	}

	static function _prevent_second_transaction( $query ) {
		if( 'START TRANSACTION' === substr( trim( $query ), 0, 17 ) ) {
			return 'SELECT "Bypassed START TRANSACTIONT transaction _prevent_second_transaction"';
		}
		return $query;
	}



	/**
	 * Retrieves the `wp_die()` handler.
	 *
	 * @param callable $handler The current die handler.
	 * @return callable The test die handler.
	 */
	public function get_wp_die_handler( $handler ) {
		return array( $this, 'wp_die_handler' );
	}


	/**
	 * Throws an exception when called.
	 *
	 * @since UT (3.7.0)
	 * @since 5.9.0 Added the `$title` and `$args` parameters.
	 *
	 * @throws WPDieException Exception containing the message and the response code.
	 *
	 * @param string|WP_Error $message The `wp_die()` message or WP_Error object.
	 * @param string          $title   The `wp_die()` title.
	 * @param string|array    $args    The `wp_die()` arguments.
	 */
	public function wp_die_handler( $message, $title, $args ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		if ( ! is_scalar( $message ) ) {
			$message = '0';
		}

		$code = 0;
		if ( isset( $args['response'] ) ) {
			$code = $args['response'];
		}

		throw new WPDieException( $message, $code );
	}



	/**
	 * Detects post-test failure conditions.
	 *
	 * We use this method to detect expectedDeprecated and expectedIncorrectUsage annotations.
	 *
	 * @since 4.2.0
	 */
	protected function assert_post_conditions() {
		if ( $this->deprecated_usage instanceof Deprecated_Usage ) {
			$this->deprecated_usage->validate();
		}
		if ( $this->doing_it_wrong instanceof Doing_It_Wrong ) {
			$this->doing_it_wrong->validate();
		}
	}


	/**
	 * Asserts that the given value is an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertWPError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'WP_Error', $actual, $message );
	}


	/**
	 *  Declares an expected `_deprecated_function()` call from within a test.
	 *
	 * An alternative to using the `@expectedDeprecated` annotation.
	 *
	 * @since  3.7.0
	 *
	 * - _deprecated_file()
	 * - _deprecated_argument()
	 * - _deprecated_hook()
	 * - _deprecated_constructor()
	 * - _deprecated_function()
	 * - _deprecated_file()
	 *
	 * @notice There used to be a different method called `expectDeprecated`.
	 *         `null` as a parameter signifies old usage and will do nothing
	 *         for the test case.
	 *
	 * @param ?string $deprecated Name of the function, method, class or
	 *                            argument that is deprecated.
	 *
	 * @return void
	 */
	public function expectDeprecated( ?string $deprecated = null ): void {
		if ( '' === $deprecated ) {
			$this->_fill_expected_deprecated();
			return; // Backward compatibility.
		}
		$this->deprecated_usage->add_expected( [ $deprecated ] );
	}


	/**
	 * Declares an expected `_doing_it_wrong()` call from within a test.
	 *
	 * An alternative to using the `@expectedIncorrectUsage` annotation.
	 *
	 * @since 3.7.0
	 *
	 * @param string $wrong
	 *
	 * @return void
	 */
	public function expectDoingItWrong( string $wrong ): void {
		$this->doing_it_wrong->add_expected( [ $wrong ] );
	}


	/**
	 * Asserts that the given value is not an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertNotWPError( $actual, $message = '' ) {
		if ( is_wp_error( $actual ) ) {
			$message .= ' ' . $actual->get_error_message();
		}

		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts that the given value is an instance of IXR_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertIXRError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'IXR_Error', $actual, $message );
	}

	/**
	 * Asserts that the given value is not an instance of IXR_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertNotIXRError( $actual, $message = '' ) {
		if ( $actual instanceof IXR_Error ) {
			$message .= ' ' . $actual->message;
		}

		$this->assertNotInstanceOf( 'IXR_Error', $actual, $message );
	}

	/**
	 * Asserts that the given fields are present in the given object.
	 *
	 * @since UT (3.7.0)
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param object $actual  The object to check.
	 * @param array  $fields  The fields to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertEqualFields( $actual, $fields, $message = '' ) {
		$this->assertIsObject( $actual, $message . ' Passed $actual is not an object.' );
		$this->assertIsArray( $fields, $message . ' Passed $fields is not an array.' );
		$this->assertNotEmpty( $fields, $message . ' Fields array is empty.' );

		foreach ( $fields as $field_name => $field_value ) {
			$this->assertObjectHasProperty( $field_name, $actual, $message . " Property $field_name does not exist on the object." );
			$this->assertSame( $field_value, $actual->$field_name, $message . " Value of property $field_name is not $field_value." );
		}
	}

	/**
	 * Asserts that two values are equal, with whitespace differences discarded.
	 *
	 * @since UT (3.7.0)
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param mixed  $expected The expected value.
	 * @param mixed  $actual   The actual value.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 */
	public function assertDiscardWhitespace( $expected, $actual, $message = '' ) {
		if ( is_string( $expected ) ) {
			$expected = preg_replace( '/\s*/', '', $expected );
		}

		if ( is_string( $actual ) ) {
			$actual = preg_replace( '/\s*/', '', $actual );
		}

		$this->assertEquals( $expected, $actual, $message );
	}

	/**
	 * Asserts that two values have the same type and value, with EOL differences discarded.
	 *
	 * @since 5.6.0
	 * @since 5.8.0 Added support for nested arrays.
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param mixed  $expected The expected value.
	 * @param mixed  $actual   The actual value.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 */
	public function assertSameIgnoreEOL( $expected, $actual, $message = '' ) {
		if ( null !== $expected ) {
			$expected = map_deep(
				$expected,
				static function ( $value ) {
					if ( is_string( $value ) ) {
						return str_replace( "\r\n", "\n", $value );
					}

					return $value;
				}
			);
		}

		if ( null !== $actual ) {
			$actual = map_deep(
				$actual,
				static function ( $value ) {
					if ( is_string( $value ) ) {
						return str_replace( "\r\n", "\n", $value );
					}

					return $value;
				}
			);
		}

		$this->assertSame( $expected, $actual, $message );
	}

	/**
	 * Asserts that two values are equal, with EOL differences discarded.
	 *
	 * @since 5.4.0
	 * @since 5.6.0 Turned into an alias for `::assertSameIgnoreEOL()`.
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param mixed  $expected The expected value.
	 * @param mixed  $actual   The actual value.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 */
	public function assertEqualsIgnoreEOL( $expected, $actual, $message = '' ) {
		$this->assertSameIgnoreEOL( $expected, $actual, $message );
	}

	/**
	 * Asserts that the contents of two un-keyed, single arrays are the same, without accounting for the order of elements.
	 *
	 * @since 5.6.0
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param array  $expected Expected array.
	 * @param array  $actual   Array to check.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 */
	public function assertSameSets( $expected, $actual, $message = '' ) {
		$this->assertIsArray( $expected, $message . ' Expected value must be an array.' );
		$this->assertIsArray( $actual, $message . ' Value under test is not an array.' );

		sort( $expected );
		sort( $actual );
		$this->assertSame( $expected, $actual, $message );
	}

	/**
	 * Asserts that the contents of two un-keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @since 3.5.0
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param array  $expected Expected array.
	 * @param array  $actual   Array to check.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 */
	public function assertEqualSets( $expected, $actual, $message = '' ) {
		$this->assertIsArray( $expected, $message . ' Expected value must be an array.' );
		$this->assertIsArray( $actual, $message . ' Value under test is not an array.' );

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual, $message );
	}


	/**
	 * Asserts that the keys of two arrays are equal, regardless of the contents,
	 * without accounting for the order of elements.
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 *
	 * @since 1.10.0
	 *
	 */
	public function assertEqualSetsIndex( $expected, $actual, $message = '' ) {
		\ksort( $expected );
		\ksort( $actual );
		$this->assertEquals( \array_keys( $expected ), \array_keys( $actual ), $message );
	}


	/**
	 * Asserts that the contents of two keyed, single arrays are the same, without accounting for the order of elements.
	 *
	 * @since 5.6.0
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param array  $expected The expected array.
	 * @param array  $actual   Array to check.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 */
	public function assertSameSetsWithIndex( $expected, $actual, $message = '' ) {
		$this->assertIsArray( $expected, $message . ' Expected value must be an array.' );
		$this->assertIsArray( $actual, $message . ' Value under test is not an array.' );

		ksort( $expected );
		ksort( $actual );
		$this->assertSame( $expected, $actual, $message );
	}

	/**
	 * Asserts that the contents of two keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @since 4.1.0
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param array  $expected Expected array.
	 * @param array  $actual   Array to check.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 */
	public function assertEqualSetsWithIndex( $expected, $actual, $message = '' ) {
		$this->assertIsArray( $expected, $message . ' Expected value must be an array.' );
		$this->assertIsArray( $actual, $message . ' Value under test is not an array.' );

		ksort( $expected );
		ksort( $actual );
		$this->assertEquals( $expected, $actual, $message );
	}


	/**
	 * Asserts the content of two arrays are equal regardless of the keys, while accounting
	 * for the order of elements
	 *
	 * @param array  $expected Expected array.
	 * @param array  $actual   Array to check.
	 * @param string $message  Message to return on failure.
	 *
	 * @since 1.9.0
	 */
	public function assertEqualSetsValues( $expected, $actual, $message = '' ) {
		$this->assertEquals( array_values( $expected ), array_values( $actual ), $message );
	}

	/**
	 * Asserts that the given variable is a multidimensional array, and that all arrays are non-empty.
	 *
	 * @since 4.8.0
	 * @since 5.9.0 Added the `$message` parameter.
	 *
	 * @param array  $actual  Array to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertNonEmptyMultidimensionalArray( $actual, $message = '' ) {
		$this->assertIsArray( $actual, $message . ' Value under test is not an array.' );
		$this->assertNotEmpty( $actual, $message . ' Array is empty.' );

		foreach ( $actual as $sub_array ) {
			$this->assertIsArray( $sub_array, $message . ' Subitem of the array is not an array.' );
			$this->assertNotEmpty( $sub_array, $message . ' Subitem of the array is empty.' );
		}
	}

	/**
	 * Checks each of the WP_Query is_* functions/properties against expected boolean value.
	 *
	 * Any properties that are listed by name as parameters will be expected to be true; all others are
	 * expected to be false. For example, assertQueryTrue( 'is_single', 'is_feed' ) means is_single()
	 * and is_feed() must be true and everything else must be false to pass.
	 *
	 * @since 2.5.0
	 * @since 3.8.0 Moved from `Tests_Query_Conditionals` to `WP_UnitTestCase`.
	 * @since 5.3.0 Formalized the existing `...$prop` parameter by adding it
	 *              to the function signature.
	 *
	 * @param string ...$prop Any number of WP_Query properties that are expected to be true for the current request.
	 */
	public function assertQueryTrue( ...$prop ) {
		global $wp_query;

		$all = array(
			'is_404',
			'is_admin',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_comment_feed',
			'is_date',
			'is_day',
			'is_embed',
			'is_feed',
			'is_front_page',
			'is_home',
			'is_privacy_policy',
			'is_month',
			'is_page',
			'is_paged',
			'is_post_type_archive',
			'is_posts_page',
			'is_preview',
			'is_robots',
			'is_favicon',
			'is_search',
			'is_single',
			'is_singular',
			'is_tag',
			'is_tax',
			'is_time',
			'is_trackback',
			'is_year',
		);

		foreach ( $prop as $true_thing ) {
			$this->assertContains( $true_thing, $all, "Unknown conditional: {$true_thing}." );
		}

		$passed  = true;
		$message = '';

		foreach ( $all as $query_thing ) {
			$result = is_callable( $query_thing ) ? call_user_func( $query_thing ) : $wp_query->$query_thing;

			if ( in_array( $query_thing, $prop, true ) ) {
				if ( ! $result ) {
					$message .= $query_thing . ' is false but is expected to be true. ' . PHP_EOL;
					$passed   = false;
				}
			} elseif ( $result ) {
				$message .= $query_thing . ' is true but is expected to be false. ' . PHP_EOL;
				$passed   = false;
			}
		}

		if ( ! $passed ) {
			$this->fail( $message );
		}
	}


	/**
	 * Assert a value matches a snapshot.
	 *
	 * @since 3.6.0
	 *
	 * @see   WP_Unit_Snapshots
	 *
	 * @param mixed  $actual  A value which may be stored in a file using print_r().
	 * @param string $message Optional. Message to display when the assertion fails.
	 * @param string $id      Optional. An identifier to be appended to the snapshot filename.
	 *
	 * @return void
	 */
	public function assertMatchesSnapshot( $actual, string $message = '', string $id = '' ): void  {
		require_once __DIR__ . '/src/Helpers/Snapshots.php';
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );

		$snapshots = Snapshots::factory( $backtrace, $id );
		$snapshots->assert_matches_snapshot( $actual, $this, $message );
	}


	/**
	 * Helper function to convert a single-level array containing text strings to a named data provider.
	 *
	 * The value of the data set will also be used as the name of the data set.
	 *
	 * Typical usage of this method:
	 *
	 *     public function data_provider_for_test_name() {
	 *         $array = array(
	 *             'value1',
	 *             'value2',
	 *         );
	 *
	 *         return $this->text_array_to_dataprovider( $array );
	 *     }
	 *
	 * The returned result will look like:
	 *
	 *     array(
	 *         'value1' => array( 'value1' ),
	 *         'value2' => array( 'value2' ),
	 *     )
	 *
	 * @since 6.1.0
	 *
	 * @param array $input Input array.
	 * @return array Array which is usable as a test data provider with named data sets.
	 */
	public static function text_array_to_dataprovider( $input ) {
		$data = array();

		foreach ( $input as $value ) {
			if ( ! is_string( $value ) ) {
				throw new Exception(
					'All values in the input array should be text strings. Fix the input data.'
				);
			}

			if ( isset( $data[ $value ] ) ) {
				throw new Exception(
					"Attempting to add a duplicate data set for value $value to the data provider. Fix the input data."
				);
			}

			$data[ $value ] = array( $value );
		}

		return $data;
	}

	/**
	 * Sets the global state to as if a given URL has been requested.
	 *
	 * This sets:
	 * - The super globals.
	 * - The globals.
	 * - The query variables.
	 * - The main query.
	 *
	 * @since 3.5.0
	 *
	 * @param string $url The URL for the request.
	 */
	public function go_to( $url ) {
		/*
		 * Note: the WP and WP_Query classes like to silently fetch parameters
		 * from all over the place (globals, GET, etc), which makes it tricky
		 * to run them more than once without very carefully clearing everything.
		 */
		$_GET  = array();
		$_POST = array();
		foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow', 'current_screen' ) as $v ) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}
		$parts = parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = isset( $parts['path'] ) ? $parts['path'] : '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the URL query vars into $_GET.
				parse_str( $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}
		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset( $_SERVER['PATH_INFO'] );

		self::flush_cache();
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];

		$public_query_vars  = $GLOBALS['wp']->public_query_vars;
		$private_query_vars = $GLOBALS['wp']->private_query_vars;

		$GLOBALS['wp']                     = new WP();
		$GLOBALS['wp']->public_query_vars  = $public_query_vars;
		$GLOBALS['wp']->private_query_vars = $private_query_vars;

		_cleanup_query_vars();

		$GLOBALS['wp']->main( $parts['query'] );
	}


	/**
	 * Skips the current test if there is an open Trac ticket associated with it.
	 *
	 * @since 3.5.0
	 *
	 * @param int $ticket_id Ticket number.
	 */
	public function knownWPBug( $ticket_id ) {
		if ( WP_TESTS_FORCE_KNOWN_BUGS || in_array( $ticket_id, self::$forced_tickets, true ) ) {
			return;
		}
		if ( ! TracTickets::isTracTicketClosed( 'https://core.trac.wordpress.org', $ticket_id ) ) {
			$this->markTestSkipped( sprintf( 'WordPress Ticket #%d is not fixed', $ticket_id ) );
		}
	}


	/**
	 * Skips the current test if there is an open Plugin Trac ticket associated with it.
	 *
	 * @since 3.5.0
	 *
	 * @param int $ticket_id Ticket number.
	 */
	public function knownPluginBug( $ticket_id ) {
		if ( WP_TESTS_FORCE_KNOWN_BUGS || in_array( 'Plugin' . $ticket_id, self::$forced_tickets, true ) ) {
			return;
		}
		if ( ! TracTickets::isTracTicketClosed( 'https://plugins.trac.wordpress.org', $ticket_id ) ) {
			$this->markTestSkipped( sprintf( 'WordPress Plugin Ticket #%d is not fixed', $ticket_id ) );
		}
	}

	/**
	 * Adds a Trac ticket number to the `$forced_tickets` property.
	 *
	 * @since 3.5.0
	 *
	 * @param int $ticket Ticket number.
	 */
	public static function forceTicket( $ticket ) {
		self::$forced_tickets[] = $ticket;
	}

	/**
	 * Custom preparations for the PHPUnit process isolation template.
	 *
	 * When restoring global state between tests, PHPUnit defines all the constants that were already defined, and then
	 * includes included files. This does not work with WordPress, as the included files define the constants.
	 *
	 * This method defines the constants after including files.
	 *
	 * @param Text_Template $template The template to prepare.
	 */
	public function prepareTemplate( Text_Template $template ) {
		$template->setVar( array( 'constants' => '' ) );
		$template->setVar( array( 'wp_constants' => PHPUnit_Util_GlobalState::getConstantsAsString() ) );
		parent::prepareTemplate( $template );
	}

	/**
	 * Creates a unique temporary file name.
	 *
	 * The directory in which the file is created depends on the environment configuration.
	 *
	 * @since 3.5.0
	 *
	 * @return string|bool Path on success, else false.
	 */
	public function temp_filename() {
		$tmp_dir = '';
		$dirs    = array( 'TMP', 'TMPDIR', 'TEMP' );

		foreach ( $dirs as $dir ) {
			if ( isset( $_ENV[ $dir ] ) && ! empty( $_ENV[ $dir ] ) ) {
				$tmp_dir = $dir;
				break;
			}
		}

		if ( empty( $tmp_dir ) ) {
			$tmp_dir = get_temp_dir();
		}

		$tmp_dir = realpath( $tmp_dir );

		return tempnam( $tmp_dir, 'wpunit' );
	}

	/**
	 * Selectively deletes a file.
	 *
	 * Does not delete a file if its path is set in the `$ignore_files` property.
	 *
	 * @param string $file File path.
	 */
	public function unlink( $file ) {
		$exists = is_file( $file );
		if ( $exists && ! in_array( $file, self::$ignore_files, true ) ) {
			//error_log( $file );
			unlink( $file );
		} elseif ( ! $exists ) {
			$this->fail( "Trying to delete a file that doesn't exist: $file" );
		}
	}

	/**
	 * Selectively deletes files from a directory.
	 *
	 * Does not delete files if their paths are set in the `$ignore_files` property.
	 *
	 * @since 4.0.0
	 *
	 * @param string $path Directory path.
	 */
	public function rmdir( $path ) {
		$files = $this->files_in_dir( $path );
		foreach ( $files as $file ) {
			if ( ! in_array( $file, self::$ignore_files, true ) ) {
				$this->unlink( $file );
			}
		}
	}

	/**
	 * Deletes files added to the `uploads` directory during tests.
	 *
	 * This method works in tandem with the `set_up()` and `rmdir()` methods:
	 * - `set_up()` scans the `uploads` directory before every test, and stores
	 *   its contents inside of the `$ignore_files` property.
	 * - `rmdir()` and its helper methods only delete files that are not listed
	 *   in the `$ignore_files` property. If called during `tear_down()` in tests,
	 *   this will only delete files added during the previously run test.
	 */
	public function remove_added_uploads() {
		$uploads = wp_upload_dir();
		$this->rmdir( $uploads['basedir'] );
	}

	/**
	 * Returns a list of all files contained inside a directory.
	 *
	 * @since 4.0.0
	 *
	 * @param string $dir Path to the directory to scan.
	 * @return array List of file paths.
	 */
	public function files_in_dir( $dir ) {
		$files = array();

		$iterator = new RecursiveDirectoryIterator( $dir );
		$objects  = new RecursiveIteratorIterator( $iterator );
		foreach ( $objects as $name => $object ) {
			if ( is_file( $name ) ) {
				$files[] = $name;
			}
		}

		return $files;
	}

	/**
	 * Returns a list of all files contained inside the `uploads` directory.
	 *
	 * @since 4.0.0
	 *
	 * @return array List of file paths.
	 */
	public function scan_user_uploads() {
		static $files = array();
		if ( ! empty( $files ) ) {
			return $files;
		}

		$uploads = wp_upload_dir();
		$files   = $this->files_in_dir( $uploads['basedir'] );
		return $files;
	}

	/**
	 * Deletes all directories contained inside a directory.
	 *
	 * @since 4.1.0
	 *
	 * @param string $path Path to the directory to scan.
	 */
	public function delete_folders( $path ) {
		if ( ! is_dir( $path ) ) {
			return;
		}

		$matched_dirs = $this->scandir( $path );

		foreach ( array_reverse( $matched_dirs ) as $dir ) {
			rmdir( $dir );
		}

		rmdir( $path );
	}

	/**
	 * Retrieves all directories contained inside a directory.
	 * Hidden directories are ignored.
	 *
	 * This is a helper for the `delete_folders()` method.
	 *
	 * @since 4.1.0
	 * @since 6.1.0 No longer sets a (dynamic) property to keep track of the directories,
	 *              but returns an array of the directories instead.
	 *
	 * @param string $dir Path to the directory to scan.
	 * @return string[] List of directories.
	 */
	public function scandir( $dir ) {
		$matched_dirs = array();

		foreach ( scandir( $dir ) as $path ) {
			if ( 0 !== strpos( $path, '.' ) && is_dir( $dir . '/' . $path ) ) {
				$matched_dirs[] = array( $dir . '/' . $path );
				$matched_dirs[] = $this->scandir( $dir . '/' . $path );
			}
		}

		/*
		 * Compatibility check for PHP < 7.4, where array_merge() expects at least one array.
		 * See: https://3v4l.org/BIQMA
		 */
		if ( array() === $matched_dirs ) {
			return array();
		}

		return array_merge( ...$matched_dirs );
	}

	/**
	 * Converts a microtime string into a float.
	 *
	 * @since 4.1.0
	 *
	 * @param string $microtime Time string generated by `microtime()`.
	 * @return float `microtime()` output as a float.
	 */
	protected function _microtime_to_float( $microtime ) {
		$time_array = explode( ' ', $microtime );
		return array_sum( $time_array );
	}

	/**
	 * Deletes a user from the database in a Multisite-agnostic way.
	 *
	 * @since 4.3.0
	 *
	 * @param int $user_id User ID.
	 * @return bool True if the user was deleted.
	 */
	public static function delete_user( $user_id ) {
		if ( is_multisite() ) {
			return wpmu_delete_user( $user_id );
		}

		return wp_delete_user( $user_id );
	}

	/**
	 * Resets permalinks and flushes rewrites.
	 *
	 * @since 4.4.0
	 *
	 * @global WP_Rewrite $wp_rewrite
	 *
	 * @param string $structure Optional. Permalink structure to set. Default empty.
	 */
	public function set_permalink_structure( $structure = '' ) {
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $structure );
		$wp_rewrite->flush_rules();
	}

	/**
	 * Creates an attachment post from an uploaded file.
	 *
	 * @since 4.4.0
	 * @since 6.2.0 Returns a WP_Error object on failure.
	 *
	 * @param array $upload         Array of information about the uploaded file, provided by wp_upload_bits().
	 * @param int   $parent_post_id Optional. Parent post ID.
	 * @return int|WP_Error The attachment ID on success, WP_Error object on failure.
	 */
	public function _make_attachment( $upload, $parent_post_id = 0 ) {
		$type = '';
		if ( ! empty( $upload['type'] ) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ( $mime ) {
				$type = $mime['type'];
			}
		}

		$attachment = array(
			'post_title'     => wp_basename( $upload['file'] ),
			'post_content'   => '',
			'post_type'      => 'attachment',
			'post_parent'    => $parent_post_id,
			'post_mime_type' => $type,
			'guid'           => $upload['url'],
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id, true );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
		);

		return $attachment_id;
	}

	/**
	 * Updates the modified and modified GMT date of a post in the database.
	 *
	 * @since 4.8.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $date    Post date, in the format YYYY-MM-DD HH:MM:SS.
	 * @return int|false 1 on success, or false on error.
	 */
	protected function update_post_modified( $post_id, $date ) {
		global $wpdb;
		return $wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => $date,
				'post_modified_gmt' => $date,
			),
			array(
				'ID' => $post_id,
			),
			array(
				'%s',
				'%s',
			),
			array(
				'%d',
			)
		);
	}

	/**
	 * Touches the given file and its directory if it doesn't already exist.
	 *
	 * This can be used to ensure a file that is implictly relied on in a test exists
	 * without it having to be built.
	 *
	 * @param string $file The file name.
	 */
	public static function touch( $file ) {
		if ( file_exists( $file ) ) {
			return;
		}

		$dir = dirname( $file );

		if ( ! file_exists( $dir ) ) {
			mkdir( $dir, 0777, true );
		}

		touch( $file );
	}
}
