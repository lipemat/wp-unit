<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  May 2025
 *
 */
class SnapshotsTest extends \WP_UnitTestCase {

	public function test_get_snapshot(): void {
		$snapshot = Snapshots::factory( [ '', [ 'class' => __CLASS__, 'function' => 'test_get_snapshot' ] ], 'identifier' );
		$this->assertSame( "Array (
    'foo' => 'bar',
    'empty' => false,
    'null' => null,
    'array' =>
      Array (
        'foo' => 'bar',
      ),
)", $snapshot->get_snapshot() );

		$data = [ 'foo' => 'bar', 'empty' => false, 'null' => null, 'array' => [ 'foo' => 'bar' ] ];
		$snapshot->update_snapshot( $data, true );
		$this->assertMatchesFullSnapshot( $data, '', 'identifier' );

		$snapshot = Snapshots::factory( [ '', [ 'class' => __CLASS__, 'function' => 'test_get_snapshot' ] ], 'print-r' );
		$this->assertSame( "Array
(
    [foo] => bar
    [empty] =>
    [null] =>
    [array] => Array
        (
            [foo] => bar
        )

)
", \implode( "\n", \array_map( '\rtrim', \explode( "\n", $snapshot->get_snapshot() ) ) ) );

		$snapshot->update_snapshot( $data, false );
		$this->assertMatchesSnapshot( $data, '', 'identifier' );
	}


	public function test_format_data(): void {
		$snapshot = Snapshots::factory( [ '', [ 'class' => __CLASS__, 'function' => 'test_format_data' ] ], 'identifier' );
		$data = [ 'foo' => 'bar', 'empty' => false, 'null' => null, 'array' => [ 'foo' => 'bar' ] ];
		$formatted = \call_private_method( $snapshot, 'format_data', [ $data ] );
		$formatted = \implode( "\n", \array_map( '\rtrim', \explode( "\n", $formatted ) ) );

		$this->assertSame( 'Array
(
    [foo] => bar
    [empty] =>
    [null] =>
    [array] => Array
        (
            [foo] => bar
        )

)
', $formatted );

		$with_falsy = \call_private_method( $snapshot, 'format_data', [ $data, true ] );
		$this->assertSame( "Array (
    'foo' => 'bar',
    'empty' => false,
    'null' => null,
    'array' =>
      Array (
        'foo' => 'bar',
      ),
)", $with_falsy );
	}


	public function test_format_whole_class(): void {
		$internal = new class() {
			public $internal_property = 'internal_value';

			private $private_internal_property = 'private_internal_value';

			private $internal_array = [
				'foo'          => 'bar',
				'baz'          => 'qux',
				'nested'       => [ 'key' => 'value' ],
				'null'         => null,
				'false'        => false,
				'empty_string' => '',
			];
		};

		$class = new class( $internal ) {
			public function __construct( $internal ) {
				$this->internal_class = $internal;
				$this->function = function() {
					return 'This is a closure function';
				};
			}


			private $function;

			private $private_property = 'private_value';

			public $public_property = 'public_value';

			protected $protected_property = 'protected_value';

			public $array_property = [
				'foo'          => 'bar',
				'baz'          => 'qux',
				'nested'       => [ 'key' => 'value' ],
				'null'         => null,
				'false'        => false,
				'empty_string' => '',
			];

			private $internal_class;
		};

		$this->assertMatchesSnapshot( $class, '', 'not-full' );
		$this->assertMatchesFullSnapshot( $class, '', 'full' );
	}


	public function test_format_wp_post_class(): void {
		$post = new \WP_Post( (object) [
			'ID'           => 1,
			'post_title'   => 'Test Post',
			'post_content' => 'This is a test post content.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_date'    => '2025-01-01 00:00:00',
			'post_author'  => 1,
		] );

		$this->assertMatchesSnapshot( $post, '', 'wp_post' );
		$this->assertMatchesFullSnapshot( $post, '', 'wp_post-full' );
	}


	public function test_get_snapshot_file_path(): void {
		$snapshot = Snapshots::factory( [ '', [ 'class' => __CLASS__, 'function' => 'test_get_snapshot_file_path' ] ], 'identifier' );
		$path = call_private_method( $snapshot, 'get_snapshot_file_path' );
		$this->assertSame( WP_TESTS_SNAPSHOTS_DIR . '\/Helpers\SnapshotsTest--test_get_snapshot_file_path-identifier-1.txt', $path );
	}
}
