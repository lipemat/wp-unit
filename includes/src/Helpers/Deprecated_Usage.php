<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

use Lipe\WP_Unit\Utils\Annotations;

/**
 * @author Mat Lipe
 * @since  3.7.0
 *
 */
final class Deprecated_Usage {

	private $expected = [];

	private $caught = [];

	/**
	 * @var \WP_UnitTestCase
	 */
	private $case;


	private function __construct( \WP_UnitTestCase $case ) {
		$this->case = $case;
	}


	private function hook(): void {
		add_filter( 'deprecated_function_trigger_error', '__return_false' );
		add_filter( 'deprecated_argument_trigger_error', '__return_false' );
		add_filter( 'deprecated_class_trigger_error', '__return_false' );
		add_filter( 'deprecated_file_trigger_error', '__return_false' );
		add_filter( 'deprecated_hook_trigger_error', '__return_false' );
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );

		add_action( 'deprecated_class_run', [ $this, 'catch_class' ], 10, 3 );
		add_action( 'deprecated_function_run', [ $this, 'catch_function' ], 10, 3 );
		add_action( 'deprecated_argument_run', [ $this, 'catch_argument' ], 10, 3 );

		add_action( 'deprecated_file_included', [ $this, 'catch_file' ], 10, 4 );
		add_action( 'deprecated_hook_run', [ $this, 'catch_hook' ], 10, 4 );
	}


	public function validate(): void {
		if ( 0 === \count( $this->expected ) && 0 === \count( $this->caught ) ) {
			return;
		}
		$errors = [];
		$not_caught_deprecated = \array_diff( $this->expected, \array_keys( $this->caught ) );
		foreach ( $not_caught_deprecated as $not_caught ) {
			$errors[] = "Failed to assert that $not_caught triggered a deprecation notice.";
		}

		$unexpected_deprecated = \array_diff( \array_keys( $this->caught ), $this->expected );

		foreach ( $unexpected_deprecated as $unexpected ) {
			$errors[] = "Unexpected deprecation notice triggered for $unexpected.";
			$errors[] = $this->caught[ $unexpected ];
		}

		$this->case::assertEmpty( $errors, implode( "\n", $errors ) );
	}


	/**
	 * @param string[] $function_or_method
	 *
	 * @return void
	 */
	public function add_expected( array $function_or_method ): void {
		$this->expected = \array_merge( $this->expected, $function_or_method );
	}


	public function get_expected( string $key ): ?string {
		return $this->expected[ $key ] ?? null;
	}


	/**
	 * @internal
	 */
	public function catch_class( $class_name, $replacement, $version ): void {
		if ( isset( $this->caught[ $class_name ] ) ) {
			return;
		}

		if ( '' !== $replacement ) {
			$this->caught[ $class_name ] = sprintf(
				'Class %1$s is deprecated since version %2$s! Use %3$s instead.',
				$class_name,
				$version,
				$replacement
			);
		} else {
			$this->caught[ $class_name ] = sprintf(
				'Class %1$s is deprecated since version %2$s with no alternative available.',
				$class_name,
				$version
			);
		}
	}


	/**
	 * @internal
	 */
	public function catch_function( $function_name, $replacement, $version ): void {
		if ( isset( $this->caught[ $function_name ] ) ) {
			return;
		}

		if ( '' !== $replacement ) {
			$this->caught[ $function_name ] = sprintf(
				'Function %1$s is deprecated since version %2$s! Use %3$s instead.',
				$function_name,
				$version,
				$replacement
			);
		} else {
			$this->caught[ $function_name ] = sprintf(
				'Function %1$s is deprecated since version %2$s with no alternative available.',
				$function_name,
				$version
			);
		}
	}


	/**
	 * @internal
	 */
	public function catch_argument( $function_name, $message, $version ): void {
		if ( isset( $this->caught[ $function_name ] ) ) {
			return;
		}

		if ( '' !== $message ) {
			$this->caught[ $function_name ] = sprintf(
				'Function %1$s was called with an argument that is deprecated since version %2$s! %3$s',
				$function_name,
				$version,
				$message
			);
		} else {
			$this->caught[ $function_name ] = sprintf(
				'Function %1$s was called with an argument that is deprecated since version %2$s with no alternative available.',
				$function_name,
				$version
			);
		}
	}


	/**
	 * @internal
	 */
	public function catch_file( $file, $replacement, $version, $message ): void {
		if ( isset( $this->caught[ $file ] ) ) {
			return;
		}

		if ( $replacement ) {
			$this->caught[ $file ] = sprintf(
				'File %1$s is deprecated since version %2$s! Use %3$s instead. %4$s',
				$file,
				$version,
				$replacement,
				$message
			);
		} else {
			$this->caught[ $file ] = sprintf(
				'File %1$s is deprecated since version %2$s with no alternative available. %3$s',
				$file,
				$version,
				$message
			);
		}
	}


	/**
	 * @internal
	 */
	public function catch_hook( $hook, $replacement, $version, $message ): void {
		if ( isset( $this->caught[ $hook ] ) ) {
			return;
		}

		if ( $replacement ) {
			$this->caught[ $hook ] = sprintf(
				'Hook %1$s is deprecated since version %2$s! Use %3$s instead. %4$s',
				$hook,
				$version,
				$replacement,
				$message
			);
		} else {
			$this->caught[ $hook ] = sprintf(
				'Hook %1$s is deprecated since version %2$s with no alternative available. %3$s',
				$hook,
				$version,
				$message
			);
		}
	}


	private function add_from_annotations(): void {
		$annotations = Annotations::instance()->get_annotations( $this->case );
		if ( isset( $annotations['class']['expectedDeprecated'] ) ) {
			foreach ( $annotations['class']['expectedDeprecated'] as $deprecated ) {
				$this->add_expected( [ $deprecated ] );
			}
		}

		if ( isset( $annotations['method']['expectedDeprecated'] ) ) {
			foreach ( $annotations['method']['expectedDeprecated'] as $deprecated ) {
				$this->add_expected( [ $deprecated ] );
			}
		}
	}


	public static function factory( \WP_UnitTestCase $case ): self {
		$class = new self( $case );
		$class->hook();
		$class->add_from_annotations();
		return $class;
	}
}
