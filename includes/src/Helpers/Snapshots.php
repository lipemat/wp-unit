<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * Snapshot testing specific to the lipemat version of wp-unit.
 *
 * - Snapshots are generated in the __snapshots__ directory in the test root.
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
	protected static array $snapshots = [];

	/**
	 * Name of the test class and method making the assertion.
	 *
	 * @var string
	 */
	protected string $test_name;

	/**
	 * Path to the test file making the assertion.
	 *
	 * @var string
	 */
	protected string $test_path;


	/**
	 * @throws \Exception -- If the `WP_TESTS_SNAPSHOTS_DIR` constant is not defined.
	 */
	public function __construct( array $backtrace = [], string $id = '' ) {
		if ( ! \defined( 'WP_TESTS_SNAPSHOTS_DIR' ) ) {
			throw new \RuntimeException( 'The `WP_TESTS_SNAPSHOTS_DIR` constant must be defined to use snapshot testing.' );
		}
		if ( ! \defined( 'WP_TESTS_SNAPSHOTS_BASE' ) ) {
			\define( 'WP_TESTS_SNAPSHOTS_BASE', '' );
		}

		$caller = \array_pop( $backtrace );
		$namespaces = \explode( '\\', \str_replace( WP_TESTS_SNAPSHOTS_BASE, '', $caller['class'] ) );
		if ( '' === WP_TESTS_SNAPSHOTS_BASE && \count( $namespaces ) > 1 ) {
			\array_shift( $namespaces );
		}
		$test_name = \array_pop( $namespaces ) . '--' . $caller['function'];
		if ( '' !== $id ) {
			$test_name .= '-' . $id;
		}
		$this->test_path = \implode( '/', $namespaces );
		if ( isset( self::$snapshots[ $test_name ] ) ) {
			++ self::$snapshots[ $test_name ];
		} else {
			self::$snapshots[ $test_name ] = 1;
		}
		$this->test_name = $test_name . '-' . self::$snapshots[ $test_name ];
	}


	public function assert_matches_snapshot( $actual, \WP_UnitTestCase_Base $test, string $message = '', bool $with_falsy = false ): void {
		$snapshot = $this->get_snapshot();
		if ( null === $snapshot ) {
			$this->update_snapshot( $actual, $with_falsy );
			\trigger_error( 'Snapshot created for ' . $this->get_test_name(), E_USER_WARNING );
			return;
		}
		if ( '' === $message ) {
			$message = $this->test_path . '/' . $this->get_test_name() . '.txt snapshot does not match!';
		}

		$test::assertSame( $snapshot, $this->format_data( $actual, $with_falsy ), $message );
	}


	public function get_snapshot() {
		$snapshot_file = $this->get_snapshot_file_path();
		if ( \file_exists( $snapshot_file ) ) {
			return \file_get_contents( $snapshot_file );
		}

		return null;
	}


	public function update_snapshot( $actual, bool $with_falsy ): void {
		$snapshots_path = $this->get_snapshots_directory();
		if ( ! \is_dir( $snapshots_path ) && ! \mkdir( $snapshots_path, 0777, true ) && ! \is_dir( $snapshots_path ) ) {
			throw new \RuntimeException( \sprintf( 'Directory "%s" was not created', $snapshots_path ) );
		}

		\file_put_contents( $this->get_snapshot_file_path(), $this->format_data( $actual, $with_falsy ) );
	}


	protected function get_test_name(): string {
		return $this->test_name;
	}


	protected function format_data( $data, bool $with_falsy = false ): string {
		if ( ! $with_falsy && ( false === $data || null === $data || '' === $data ) ) {
			return '';
		}
		if ( $with_falsy ) {
			$data = $this->formatted_var_export( $data );
		} elseif ( ! \is_scalar( $data ) ) {
			$data = \print_r( $data, true );
		}

		return $this->normalize_line_endings( $data );
	}


	protected function formatted_var_export( $value ) {
		$export = var_export( $value, true );

		$lines = \explode( "\n", $export );
		$export = \array_map( function( $line ) {
			$line = \rtrim( $line );
			$line = \preg_replace_callback( '/^( {2})+/', function( $matches ) {
				return \str_repeat( '    ', \strlen( $matches[0] ) / 2 );
			}, $line );

			return \preg_replace(
				[
					'/(\s*?)\\\\?class@anonymous.\S+?::__set_state\(array/',
					'/(\s*?)\\\\?Clousure::__set_state\(array/',
					'/(\s*?)\\\\?(\S+)::__set_state\(array/',
					'/^(\'?)array \(/',
					'/(\s*?)array \(/',
					'/(\s*?)\)\),$/',
					'/(\s*?)\),$/',
					'/(\s*?)\)\)$/',
				],
				[
					'$1$1class@anonymous Object ',
					'$1$1Closure Object ',
					'$1$1$2 Object ',
					'$1Array (',
					'$1  Array (',
					'$1    )',
					'$1  ),',
					'$1)',
				],
				$line
			);
		}, $lines );
		$export = \str_replace( 'NULL', 'null', $export );

		return \implode( "\n", $export );
	}

	protected function normalize_line_endings( $string ) {
		return \str_replace( "\r\n", "\n", $string );
	}

	protected function get_snapshot_file_path(): string {
		return $this->get_snapshots_directory() . DIRECTORY_SEPARATOR . $this->get_test_name() . '.txt';
	}


	protected function get_snapshots_directory(): string {
		return WP_TESTS_SNAPSHOTS_DIR . DIRECTORY_SEPARATOR . $this->test_path;
	}


	public static function factory( array $backtrace, string $id = '' ): self {
		return new self( $backtrace, $id );
	}
}
