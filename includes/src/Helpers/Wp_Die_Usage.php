<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  3.8.0
 *
 */
final class Wp_Die_Usage {
	/**
	 * @var array<string, array{code: int|null}>
	 */
	private $expected = [];

	/**
	 * @var array<string, array{title: string, code: int}>
	 */
	private $caught = [];

	/**
	 * @var \WP_UnitTestCase_Base
	 */
	private $case;


	final private function __construct( \WP_UnitTestCase_Base $case ) {
		$this->case = $case;
		add_filter( 'wp_die_handler', [ $this, 'get_handler' ], 0 );
	}


	public function get_handler(): array {
		return [ $this, 'handler' ];
	}


	public function add_expected( $message, $code = null ) {
		$this->expected[ $message ] = [
			'code' => $code,
		];
	}


	/**
	 * Track the wp_die call so can validate it at the end of the test case.
	 *
	 * @todo Version, 4 Remove the backwards compatibility throw at the bottom.
	 *
	 * @param string|\WP_Error $message
	 * @param string|int       $title
	 * @param array{
	 *    response?: int,
	 *    link_url?: string,
	 *    back_link?: string,
	 *    text_direction?: 'rtl'|'ltr',
	 *    charset?: string,
	 *    code?: string,
	 *    exit?: bool
	 * }                       $args
	 *
	 * @throws \WPDieException
	 * @return void
	 */
	public function handler( $message, $title, array $args ) {
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

		$this->caught[ $message ] = [
			'title' => $title,
			'code'  => (int) $code,
		];
		if ( \count( $this->expected ) === 0 ) {
			// Original implementation before the handler was introduced.
			throw new \WPDieException( $message, $code );
		}
	}


	/**
	 * @todo Version 4, Update the 0 === count() to return only when both caught and expected are empty.
	 *
	 * @return void
	 */
	public function validate() {
		// Backwards compatibility handled by `throw`.
		if ( 0 === \count( $this->expected ) ) {
			return;
		}
		$errors = [];
		$not_caught_wp_die = \array_diff( \array_keys( $this->expected ), \array_keys( $this->caught ) );
		foreach ( $not_caught_wp_die as $not_caught ) {
			$errors[] = "Expected wp_die call '{$not_caught}' was not made.";
		}
		$unexpected_wp_die = \array_diff( \array_keys( $this->caught ), \array_keys( $this->expected ) );
		foreach ( $unexpected_wp_die as $unexpected ) {
			$errors[] = "Unexpected wp_die call '{$unexpected}' was made.";
		}

		foreach ( $this->caught as $message => $args ) {
			if ( isset( $this->expected[ $message ] ) && null !== $this->expected[ $message ]['code'] && $args['code'] !== $this->expected[ $message ]['code'] ) {
				$errors[] = "Expected wp_die call for {$message} did not have the expected code {$this->expected[$message]['code']}.";
			}
		}
		$this->case->assertEmpty( $errors, implode( "\n", $errors ) );
	}


	public static function factory( \WP_UnitTestCase_Base $case ): self {
		return new self( $case );
	}
}
