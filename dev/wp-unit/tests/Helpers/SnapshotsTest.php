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
		$this->assertSame( "[
    'foo' => 'bar',
    'empty' => false,
    'null' => null,
    'array' => [
        'foo' => 'bar',
    ],
]", $snapshot->get_snapshot() );

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

		$data = [ 'foo' => 'bar', 'empty' => false, 'null' => null, 'array' => [ 'foo' => 'bar' ] ];
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
		$this->assertSame( "[
    'foo' => 'bar',
    'empty' => false,
    'null' => null,
    'array' => [
        'foo' => 'bar',
    ],
]", $with_falsy );
	}


	public function test_get_snapshot_file_path(): void {
		$snapshot = Snapshots::factory( [ '', [ 'class' => __CLASS__, 'function' => 'test_get_snapshot_file_path' ] ], 'identifier' );
		$path = call_private_method( $snapshot, 'get_snapshot_file_path' );
		$this->assertSame( WP_TESTS_SNAPSHOTS_DIR . '\/Helpers\SnapshotsTest--test_get_snapshot_file_path-identifier-1.txt', $path );
	}
}
