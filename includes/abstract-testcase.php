<?php

use Lipe\WP_Unit\Helpers\Cleanup;
use Lipe\WP_Unit\Helpers\DatabaseTransactions;
use Lipe\WP_Unit\Helpers\Deprecated_Usage;
use Lipe\WP_Unit\Helpers\Doing_It_Wrong;
use Lipe\WP_Unit\Helpers\Global_Hooks;
use Lipe\WP_Unit\Helpers\Hook_State;
use Lipe\WP_Unit\Helpers\Setup_Teardown_State;
use Lipe\WP_Unit\Helpers\Snapshots;
use Lipe\WP_Unit\Helpers\Wp_Die_Usage;

require_once __DIR__ . '/factory.php';

/**
 * Do not use this class directly. Instead, extend `WP_UnitTestCase`.
 *
 * - Defines a basic fixture to run multiple tests.
 * - Resets the state of the WordPress installation before and after every test.
 * - Includes assertions useful for testing WordPress.
 */
abstract class WP_UnitTestCase_Base extends PHPUnit_Adapter_TestCase {
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

	/**
	 * @var ?Wp_Die_Usage
	 */
	protected $wp_die_usage;


	/**
	 * Fetches the factory object for generating WordPress fixtures.
	 *
	 * @return \WP_UnitTest_Factory
	 */
	protected static function factory(): \WP_UnitTest_Factory {
		static $factory = null;
		if ( null === $factory ) {
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
		$wpdb->show_errors = true;
		$wpdb->db_connect();
		ini_set( 'display_errors', '1' );

		$class = static::class;

		DatabaseTransactions::instance()->commit_transaction();
		Setup_Teardown_State::set_up_before_class( $class );
	}


	/**
	 * Runs the routine after all tests have been run.
	 */
	public static function tear_down_after_class() {
		$class = static::class;

		if ( ! tests_skip_install() ) {
			_delete_all_data();
		}
		Cleanup::instance()->flush_cache();

		DatabaseTransactions::instance()->commit_transaction();

		Global_Hooks::instance()->restore_globals();
		Setup_Teardown_State::tear_down_after_class( $class );

		parent::tear_down_after_class();
	}


	/**
	 * Runs the routine before each test is executed.
	 */
	public function set_up() {
		set_time_limit( 0 );

		$this->hook_state = Hook_State::factory();

		// Load the helpers into the stack.
		$this->deprecated_usage = Deprecated_Usage::factory( $this );
		$this->doing_it_wrong = Doing_It_Wrong::factory( $this );
		$this->wp_die_usage = Wp_Die_Usage::factory( $this );

		Cleanup::instance()->clean_up_global_scope();
		Cleanup::instance()->reset__SERVER();

		DatabaseTransactions::instance()->start_transaction();

		Setup_Teardown_State::set_up();
	}


	/**
	 * After a test method runs, resets any state in WordPress the test method might have changed.
	 */
	public function tear_down() {
		global $wpdb, $wp_the_query, $wp_query, $wp;
		$wpdb->query( 'ROLLBACK' );
		if ( is_multisite() ) {
			while ( ms_is_switched() ) {
				restore_current_blog();
			}
		}

		// Reset query, main query, and WP globals similar to wp-settings.php.
		$wp_the_query = new WP_Query();
		$wp_query = $wp_the_query;
		$wp = new WP();

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
		$current_screen_globals = [ 'current_screen', 'taxnow', 'typenow' ];
		foreach ( $current_screen_globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		// Reset comment globals.
		$comment_globals = [ 'comment_alt', 'comment_depth', 'comment_thread_alt' ];
		foreach ( $comment_globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		// Reset menu globals.
		$menu_globals = [ 'menu', 'submenu', 'parent_file', 'submenu_file', 'plugin_page', '_wp_submenu_nopriv', '_wp_real_parent_file', '_registered_pages', '_parent_pages', 'admin_page_hooks' ];
		foreach ( $menu_globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		/*
		 * Reset $wp_sitemap global so that sitemap-related dynamic $wp->public_query_vars
		 * are added when the next test runs.
		 */
		$GLOBALS['wp_sitemaps'] = null;

		// Reset template globals.
		$GLOBALS['wp_stylesheet_path'] = null;
		$GLOBALS['wp_template_path'] = null;

		Cleanup::instance()->unregister_all_meta_keys();
		remove_filter( 'query', [ $this, '_create_temporary_tables' ] );
		remove_filter( 'query', [ $this, '_drop_temporary_tables' ] );

		// Reset a project container if available.
		if ( function_exists( 'tests_reset_container' ) ) {
			tests_reset_container();
		}
		// Reset the PHP mailer.
		reset_phpmailer_instance();

		if ( $this->hook_state instanceof Hook_State ) {
			Global_Hooks::instance()->restore_hooks( $this->hook_state );
		}
		wp_set_current_user( 0 );

		Cleanup::instance()->reset_lazyload_queue();

		Setup_Teardown_State::tear_down();
	}


	/**
	 * Allows tests to be skipped when Multisite is not in use.
	 *
	 * Use with the ms-required group.
	 */
	public function skipWithoutMultisite(): void {
		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Test only runs on Multisite' );
		}
	}


	/**
	 * Allows tests to be skipped when Multisite is in use.
	 *
	 * Use with the ms-excluded group.
	 */
	public function skipWithMultisite(): void {
		if ( is_multisite() ) {
			self::markTestSkipped( 'Test does not run on Multisite' );
		}
	}


	/**
	 * Allows tests to be skipped if the HTTP request times out.
	 *
	 * @param array|WP_Error $response HTTP response.
	 */
	public function skipTestOnTimeout( $response ): void {
		if ( ! is_wp_error( $response ) ) {
			return;
		}
		if ( 'connect() timed out!' === $response->get_error_message() ) {
			self::markTestSkipped( 'HTTP timeout' );
		}

		if ( false !== strpos( $response->get_error_message(), 'timed out after' ) ) {
			self::markTestSkipped( 'HTTP timeout' );
		}

		if ( 0 === strpos( $response->get_error_message(), 'stream_socket_client(): unable to connect to tcp://s.w.org:80' ) ) {
			self::markTestSkipped( 'HTTP timeout' );
		}
	}


	/**
	 * Detects post-test failure conditions.
	 *
	 * We use this method to detect expectedDeprecated and expectedIncorrectUsage annotations.
	 *
	 * @since 4.2.0
	 */
	protected function assert_post_conditions(): void {
		if ( $this->deprecated_usage instanceof Deprecated_Usage ) {
			$this->deprecated_usage->validate();
		}
		if ( $this->doing_it_wrong instanceof Doing_It_Wrong ) {
			$this->doing_it_wrong->validate();
		}
		if ( $this->wp_die_usage instanceof Wp_Die_Usage ) {
			$this->wp_die_usage->validate();
		}
	}


	/**
	 * Asserts that the given value is an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertWPError( $actual, string $message = '' ): void {
		self::assertInstanceOf( 'WP_Error', $actual, $message );
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
	 * @param string $deprecated      Name of the function, method, class or
	 *                                argument that is deprecated.
	 *
	 * @return void
	 */
	public function expectDeprecated( string $deprecated ): void {
		$this->deprecated_usage->add_expected( [ $deprecated ] );
	}


	/**
	 * Declares an expected `_doing_it_wrong()` call from within a test.
	 *
	 * An alternative to using the `@expectedIncorrectUsage` annotation.
	 *
	 * @since 3.7.0
	 *
	 * @param string  $function_name Name of the function passed to `_doing_it_wrong`.
	 * @param ?string $message       Optional. Message to also validate.
	 *
	 * @return void
	 */
	public function expectDoingItWrong( string $function_name, ?string $message = null ): void {
		$this->doing_it_wrong->add_expected( $function_name, $message );
	}


	/**
	 * Declares an expected `wp_die()` call from within a test.
	 *
	 * @since 3.8.0
	 *
	 * @param string   $message
	 * @param int|null $code
	 *
	 * @return void
	 */
	public function expectWpDie( string $message, ?int $code = null ): void {
		$this->wp_die_usage->add_expected( $message, $code );
	}


	/**
	 * Asserts that the given value is not an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertNotWPError( $actual, string $message = '' ) {
		if ( is_wp_error( $actual ) ) {
			$message .= ' ' . $actual->get_error_message();
		}

		self::assertNotInstanceOf( 'WP_Error', $actual, $message );
	}


	/**
	 * Asserts that the given value is an instance of IXR_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertIXRError( $actual, string $message = '' ) {
		self::assertInstanceOf( 'IXR_Error', $actual, $message );
	}


	/**
	 * Asserts that the given value is not an instance of IXR_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertNotIXRError( $actual, string $message = '' ) {
		if ( $actual instanceof IXR_Error ) {
			$message .= ' ' . $actual->message;
		}

		self::assertNotInstanceOf( 'IXR_Error', $actual, $message );
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
	public function assertEqualFields( object $actual, array $fields, string $message = '' ) {
		self::assertIsObject( $actual, $message . ' Passed $actual is not an object.' );
		self::assertIsArray( $fields, $message . ' Passed $fields is not an array.' );
		self::assertNotEmpty( $fields, $message . ' Fields array is empty.' );

		foreach ( $fields as $field_name => $field_value ) {
			self::assertObjectHasProperty( $field_name, $actual, $message . " Property $field_name does not exist on the object." );
			// @phpstan-ignore-next-line -- Using variable property access.
			self::assertSame( $field_value, $actual->{$field_name}, $message . " Value of property {$field_name} is not $field_value." );
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

		self::assertEquals( $expected, $actual, $message );
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
				static function( $value ) {
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
				static function( $value ) {
					if ( is_string( $value ) ) {
						return str_replace( "\r\n", "\n", $value );
					}

					return $value;
				}
			);
		}

		self::assertSame( $expected, $actual, $message );
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
		self::assertIsArray( $expected, $message . ' Expected value must be an array.' );
		self::assertIsArray( $actual, $message . ' Value under test is not an array.' );

		\sort( $expected );
		\sort( $actual );
		self::assertSame( $expected, $actual, $message );
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
		self::assertIsArray( $expected, $message . ' Expected value must be an array.' );
		self::assertIsArray( $actual, $message . ' Value under test is not an array.' );

		\sort( $expected );
		\sort( $actual );
		self::assertEquals( $expected, $actual, $message );
	}


	/**
	 * Asserts that the keys of two arrays are equal, regardless of the contents,
	 * without accounting for the order of elements.
	 *
	 * @since 1.10.0
	 *
	 * @param array  $actual   Array to check.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 *
	 * @param array  $expected Expected array.
	 */
	public function assertEqualSetsIndex( $expected, $actual, $message = '' ) {
		\ksort( $expected );
		\ksort( $actual );
		self::assertEquals( \array_keys( $expected ), \array_keys( $actual ), $message );
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
		self::assertIsArray( $expected, $message . ' Expected value must be an array.' );
		self::assertIsArray( $actual, $message . ' Value under test is not an array.' );

		\ksort( $expected );
		\ksort( $actual );
		self::assertSame( $expected, $actual, $message );
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
	public function assertEqualSetsWithIndex( array $expected, array $actual, string $message = '' ) {
		self::assertIsArray( $expected, $message . ' Expected value must be an array.' );
		self::assertIsArray( $actual, $message . ' Value under test is not an array.' );

		\ksort( $expected );
		\ksort( $actual );
		self::assertEquals( $expected, $actual, $message );
	}


	/**
	 * Asserts the content of two arrays are equal regardless of the keys, while accounting
	 * for the order of elements
	 *
	 * @since 1.9.0
	 *
	 * @param array  $actual   Array to check.
	 * @param string $message  Message to return on failure.
	 *
	 * @param array  $expected Expected array.
	 */
	public function assertEqualSetsValues( array $expected, array $actual, string $message = '' ) {
		self::assertEquals( \array_values( $expected ), \array_values( $actual ), $message );
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
	public function assertNonEmptyMultidimensionalArray( array $actual, string $message = '' ) {
		self::assertIsArray( $actual, $message . ' Value under test is not an array.' );
		self::assertNotEmpty( $actual, $message . ' Array is empty.' );

		foreach ( $actual as $sub_array ) {
			self::assertIsArray( $sub_array, $message . ' Subitem of the array is not an array.' );
			self::assertNotEmpty( $sub_array, $message . ' Subitem of the array is empty.' );
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

		$all = [
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
		];

		foreach ( $prop as $true_thing ) {
			self::assertContains( $true_thing, $all, "Unknown conditional: {$true_thing}." );
		}

		$passed = true;
		$message = '';

		foreach ( $all as $query_thing ) {
			// @phpstan-ignore-next-line -- Using variable property access.
			$result = is_callable( $query_thing ) ? $query_thing() : $wp_query->{$query_thing};

			if ( in_array( $query_thing, $prop, true ) ) {
				if ( ! $result ) {
					$message .= $query_thing . ' is false but is expected to be true. ' . PHP_EOL;
					$passed = false;
				}
			} elseif ( $result ) {
				$message .= $query_thing . ' is true but is expected to be false. ' . PHP_EOL;
				$passed = false;
			}
		}

		if ( ! $passed ) {
			self::fail( $message );
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
	public function assertMatchesSnapshot( $actual, string $message = '', string $id = '' ): void {
		require_once __DIR__ . '/src/Helpers/Snapshots.php';
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );

		$snapshots = Snapshots::factory( $backtrace, $id );
		$snapshots->assert_matches_snapshot( $actual, $this, $message );
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
	public function go_to( string $url ): void {
		/*
		 * Note: the WP and WP_Query classes like to silently fetch parameters
		 * from all over the place (globals, GET, etc), which makes it tricky
		 * to run them more than once without very carefully clearing everything.
		 */
		$_GET = [];
		$_POST = [];
		foreach ( [ 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow', 'current_screen' ] as $v ) {
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

		Cleanup::instance()->flush_cache();
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

		$public_query_vars = $GLOBALS['wp']->public_query_vars;
		$private_query_vars = $GLOBALS['wp']->private_query_vars;

		$GLOBALS['wp'] = new WP();
		$GLOBALS['wp']->public_query_vars = $public_query_vars;
		$GLOBALS['wp']->private_query_vars = $private_query_vars;

		// @phpstan-ignore-next-line -- Private WP core function.
		_cleanup_query_vars();

		$GLOBALS['wp']->main( $parts['query'] );
	}
}
