<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

use Lipe\WP_Unit\Utils\Annotations;

/**
 * @author Mat Lipe
 * @since  March 2024
 *
 */
final class Doing_It_Wrong {
	private $expected = [];

	private $caught = [];

	/**
	 * @var \WP_UnitTestCase_Base
	 */
	private $case;


	private function __construct( \WP_UnitTestCase_Base $case ) {
		$this->case = $case;
	}


	private function hook(): void {
		add_action( 'doing_it_wrong_trigger_error', '__return_false' );
		add_action( 'doing_it_wrong_run', [ $this, 'catch' ], 10, 3 );
	}


	public function validate(): void {
		if ( 0 === \count( $this->expected ) && 0 === \count( $this->caught ) ) {
			return;
		}
		$errors = [];
		$not_caught_wrong = \array_diff( $this->expected, \array_keys( $this->caught ) );
		foreach ( $not_caught_wrong as $not_caught ) {
			$errors[] = "Failed to assert that $not_caught triggered a doing it wrong notice.";
		}

		$unexpected_wrong = \array_diff( \array_keys( $this->caught ), $this->expected );
		foreach ( $unexpected_wrong as $unexpected ) {
			$errors[] = "Unexpected doing it wrong notice triggered for $unexpected.";
		}

		$this->case->assertEmpty( $errors, \implode( "\n", $errors ) );
	}


	/**
	 * @param string[] $function_or_method
	 *
	 * @return void
	 */
	public function add_expected( array $function_or_method ): void {
		$this->expected = \array_merge( $this->expected, $function_or_method );
	}


	public function get_expected( string $function_name ): ?string {
		return $this->expected[ $function_name ] ?? null;
	}


	public function catch( string $function_name, string $message, string $version ): void {
		if ( isset( $this->caught[ $function_name ] ) ) {
			return;
		}
		if ( '' !== $version ) {
			$message .= ' ' . \sprintf( '(This message was added in version %s.)', $version );
		}
		$this->caught[ $function_name ] = $message;
	}


	private function add_from_annotations(): void {
		$annotations = Annotations::instance()->get_annotations( $this->case );
		if ( isset( $annotations['class']['expectedIncorrectUsage'] ) ) {
			foreach ( $annotations['class']['expectedIncorrectUsage'] as $deprecated ) {
				$this->add_expected( [ $deprecated ] );
			}
		}

		if ( isset( $annotations['method']['expectedIncorrectUsage'] ) ) {
			foreach ( $annotations['method']['expectedIncorrectUsage'] as $deprecated ) {
				$this->add_expected( [ $deprecated ] );
			}
		}
	}


	public static function factory( \WP_UnitTestCase_Base $case ): self {
		$class = new self( $case );
		$class->hook();
		$class->add_from_annotations();
		return $class;
	}
}
