<?php

namespace Lipe\WP_Unit\Helpers;

/**
 * Snapshot testing specific to the lipemat version of wp-unit.
 *
 * - Snapshots are generated in the __snapshots__ directory in test root.
 * - Multiple files are generated for test cases with multiple snapshot assertions.
 *
 * @author Mat Lipe
 * @since  3.6.0
 *
 */
class Snapshots {
	/**
	 * Track multiple snapshots for the same test.
	 *
	 * @var array<string, int>
	 */
	protected static $snapshots = [];

	/**
	 * @var string
	 */
	protected $test_name;


	public function __construct( array $backtrace = [] ) {
		$caller = \array_pop( $backtrace );
		$test_name = \str_replace( '\\', '__', $caller['class'] ) . '--' . $caller['function'];

		if ( isset( self::$snapshots[ $test_name ] ) ) {
			++ self::$snapshots[ $test_name ];
		} else {
			self::$snapshots[ $test_name ] = 1;
		}
		$this->test_name = $test_name . '-' . self::$snapshots[ $test_name ];
	}


	public function assert_matches_snapshot( $actual, \WP_UnitTestCase_Base $test, string $message = '' ): void {
		$snapshot = $this->get_snapshot();
		if ( null === $snapshot ) {
			$this->update_snapshot( $actual );
			$test->addWarning( 'Snapshot created for ' . $this->get_test_name() );
			return;
		}
		$test->assertSame( $snapshot, $this->format_data( $actual ), $message );
	}


	public function get_snapshot() {
		$snapshot_file = $this->get_snapshot_file_name();
		if ( file_exists( $snapshot_file ) ) {
			return file_get_contents( $snapshot_file );
		}

		return null;
	}


	public function update_snapshot( $actual ): void {
		$snapshots_path = $this->get_snapshots_path();
		if ( ! is_dir( $snapshots_path ) ) {
			mkdir( $snapshots_path );
		}

		file_put_contents( $this->get_snapshot_file_name(), $this->format_data( $actual ) );
	}


	protected function get_test_name(): string {
		return $this->test_name;
	}


	protected function format_data( $data ): string {
		if ( ! is_scalar( $data ) ) {
			$data = print_r( $data, true );
		}

		return $this->normalize_line_endings( $data );
	}


	protected function normalize_line_endings( $string ) {
		return str_replace( "\r\n", "\n", $string );
	}


	protected function get_snapshot_file_name(): string {
		return $this->get_snapshots_path() . DIRECTORY_SEPARATOR . $this->get_test_name() . '.txt';
	}

	protected function get_snapshots_path(): string {
		$dir = getcwd();

		return $dir . DIRECTORY_SEPARATOR . '__snapshots__';
	}


	public static function factory( array $backtrace ): self {
		return new self( $backtrace );
	}
}
