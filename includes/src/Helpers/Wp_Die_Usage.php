<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * Support testing for expected `wp_die` calls or
 * throwing `WPDieException` if no expected calls are set.
 *
 * Like the original throw implementation, but with the ability to
 * test for specific messages and codes.
 *
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
		add_filter( 'wp_die_handler', [ $this, 'get_handler' ] );
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
	 * If not expected wp_die calls are set, the original implementation of throwing
	 * `WPDieException` will be used.
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
	 * Validate any expected `wp_die` calls at the end of the test case.
	 *
	 * If no expected calls are set, no validation will be done in favor
	 * of throwing `WPDieException` as the original implementation did.
	 *
	 * @return void
	 */
	public function validate() {
		// Original implementation before the handler was introduced.
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
