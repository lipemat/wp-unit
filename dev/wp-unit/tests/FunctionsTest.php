<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit;

/**
 * Test the functions.php file.
 *
 * @author Mat Lipe
 * @since  January 2026
 *
 */
class FunctionsTest extends \WP_UnitTestCase {
	public function test_tests_add_option(): void {
		$this->assertSame( 'strapped-on', get_option( 'bootstrap/testing' ) );
		$this->assertSame( 'strapped-on', get_network_option( null, 'bootstrap/testing' ) );

		update_option( 'bootstrap/testing', 'strapped-off' );
		$this->assertSame( 'strapped-off', get_option( 'bootstrap/testing' ) );
		$this->assertSame( 'strapped-on', get_network_option( null, 'bootstrap/testing' ) );

		update_network_option( null, 'bootstrap/testing', 'strapped-off' );
		$this->assertSame( 'strapped-off', get_option( 'bootstrap/testing' ) );
		$this->assertSame( 'strapped-off', get_network_option( null, 'bootstrap/testing' ) );

		$this->assertSame( 'editor', get_option( 'default_role' ) );
		$this->assertSame( 'editor', get_network_option( null, 'default_role' ) );
	}


	/**
	 * @dataProvider providerCallbacks
	 *
	 * @param callable $callback
	 * @param string   $message
	 */
	public function test_filter_build_unique_id_matches_core( callable $callback, string $message ): void {
		$this->assertSame( _wp_filter_build_unique_id( 'test_hook', $callback, 10 ), _test_filter_build_unique_id( 'test_hook', $callback, 10 ), $message );
	}


	public function test_filter_build_unique_id_distinct_objects(): void {
		$first = new \ArrayObject();
		$second = new \ArrayObject();

		$this->assertNotSame( _test_filter_build_unique_id( 'test_hook', [ $first, 'count' ], 10 ), _test_filter_build_unique_id( 'test_hook', [ $second, 'count' ], 10 ) );
		$this->assertSame( _test_filter_build_unique_id( 'test_hook', [ $first, 'count' ], 10 ), _test_filter_build_unique_id( 'test_hook', [ $first, 'count' ], 10 ) );
	}


	public function test_filter_build_unique_id_closure_matches_core(): void {
		$callback = function() {
		};

		$this->assertSame( _wp_filter_build_unique_id( 'test_hook', $callback, 10 ), _test_filter_build_unique_id( 'test_hook', $callback, 10 ) );
	}


	public function test_filter_build_unique_id_closure_string_key(): void {
		$idx = _test_filter_build_unique_id( 'test_hook', function() {
		}, 10 );
		$keys = \array_keys( [ $idx => true ] );

		$this->assertSame( [ $idx ], $keys );
	}


	/**
	 * @return array<string, array{callback: callable, message: string}>
	 */
	public static function providerCallbacks(): array {
		return [
			'function name'        => [
				'callback' => '__return_true',
				'message'  => 'Function name callbacks must match core.',
			],
			'static method string' => [
				'callback' => 'WP_Hook::build_preinitialized_hooks',
				'message'  => 'Static method strings must match core.',
			],
			'static method array'  => [
				'callback' => [ 'WP_Hook', 'build_preinitialized_hooks' ],
				'message'  => 'Static method arrays must match core.',
			],
			'object method'        => [
				'callback' => [ new \ArrayObject(), 'count' ],
				'message'  => 'Object method callbacks must match core.',
			],
		];
	}
}
