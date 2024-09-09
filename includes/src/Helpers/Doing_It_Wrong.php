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
	/**
	 * @var array<string, array<string|null>>
	 */
	private $expected = [];

	/**
	 * @var array<string, array<string|null>>
	 */
	private $caught = [];

	/**
	 * @var \WP_UnitTestCase_Base
	 */
	private $case;


	private function __construct( \WP_UnitTestCase_Base $case ) {
		$this->case = $case;
	}


	private function hook(): void {
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
		add_action( 'doing_it_wrong_run', [ $this, 'catch' ], 10, 3 );
	}


	public function validate(): void {
		if ( 0 === \count( $this->expected ) && 0 === \count( $this->caught ) ) {
			return;
		}
		$errors = [];
		foreach ( $this->expected as $function_name => $messages ) {
			if ( isset( $this->caught[ $function_name ] ) ) {
				if ( \count( $this->expected[ $function_name ] ) > \count( $this->caught[ $function_name ] ) ) {
					$errors[] = "Failed to assert that {$function_name} triggered " . \count( $this->expected[ $function_name ] ) . ' doing it wrong notices. Got ' . \count( $this->caught[ $function_name ] ) . ' instead.';
					\array_walk( $messages, function( $message ) use ( &$errors ) {
						if ( null !== $message ) {
							$errors[] = $message;
						}
					} );
				}
			} else {
				$errors[] = "Failed to assert that {$function_name} triggered " . \count( $this->expected[ $function_name ] ) . ' doing it wrong notices.';
				\array_walk( $messages, function( $message ) use ( &$errors ) {
					if ( null !== $message ) {
						$errors[] = $message;
					}
				} );
			}
		}

		foreach ( $this->caught as $function_name => $messages ) {
			if ( isset( $this->expected[ $function_name ] ) ) {
				if ( \count( $this->caught[ $function_name ] ) > \count( $this->expected[ $function_name ] ) ) {
					$errors[] = "Unexpected doing it wrong notices triggered for {$function_name}. Expected " . \count( $this->expected[ $function_name ] ) . ' but got ' . \count( $this->caught[ $function_name ] ) . ' instead.';
					$errors[] = \implode( "\n", $this->caught[ $function_name ] );
				}
			} else {
				$errors[] = \count( $this->caught[ $function_name ] ) . " unexpected doing it wrong notices triggered for {$function_name}.";
				\array_walk( $messages, function( ?string $message ) use ( &$errors ) {
					if ( null !== $message ) {
						$errors[] = $message;
					}
				} );
			}
		}

		foreach ( $this->expected as $function_name => $messages ) {
			$unmatched = \array_diff( $this->expected[ $function_name ], $this->caught[ $function_name ] ?? [] );
			foreach ( $unmatched as $key => $message ) {
				if ( null !== $message ) {
					$received = $this->caught[ $function_name ][ $key ] ?? 'unknown';
					$errors[] = "The expected \"doing it wrong\" message for `{$function_name} was`: \n \"{$message}\" \n \nReceived: \n \"{$received}\". \n";
				}
			}
		}

		$this->case::assertCount( 0, $errors, \implode( "\n", $errors ) );
	}


	/**
	 * @param string  $function_name - Function name passed to the `doing_it_wrong` function.
	 * @param ?string $message       - Optional message to also validate
	 *
	 * @return void
	 */
	public function add_expected( string $function_name, ?string $message = null ): void {
		if ( ! isset( $this->expected[ $function_name ] ) ) {
			$this->expected[ $function_name ] = [];
		}
		$this->expected[ $function_name ][] = $message;
	}


	/**
	 * @return null|string[]
	 */
	public function get_expected( string $function_name ): ?array {
		return $this->expected[ $function_name ] ?? null;
	}


	public function catch( string $function_name, string $message, $version ): void {
		if ( '' !== $version && null !== $version ) {
			$message .= ' ' . \sprintf( '(This message was added in version %s.)', $version );
		}
		if ( ! isset( $this->caught[ $function_name ] ) ) {
			$this->caught[ $function_name ] = [];
		}
		$this->caught[ $function_name ][] = $message;
	}


	private function add_from_annotations(): void {
		$annotations = Annotations::instance()->get_annotations( $this->case );
		if ( isset( $annotations['class']['expectedIncorrectUsage'] ) ) {
			foreach ( $annotations['class']['expectedIncorrectUsage'] as $deprecated ) {
				$this->add_expected( $deprecated );
			}
		}

		if ( isset( $annotations['method']['expectedIncorrectUsage'] ) ) {
			foreach ( $annotations['method']['expectedIncorrectUsage'] as $deprecated ) {
				$this->add_expected( $deprecated );
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
