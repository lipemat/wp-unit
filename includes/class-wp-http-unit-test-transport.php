<?php

use Lipe\WP_Unit\Rules\Http_Transport;
use Lipe\WP_Unit\Utils\Requests;

/**
 *
 * Before WP 6.4, we need to filter the transports class for the requests to use it.
 *
 * WP will automatically add 'WP_Http_' to the class name, so it must exist in the global
 * namespace in that format.
 *
 * We can't use the test case class because PHPUnit 10 requires a parameter to be passed when
 * constructing the class and WP does not pass anything when constructing the transport class.
 *
 * A trait is used to allow sharing the methods between the test case, and the transport class.
 *
 * @author Mat Lipe
 * @since  3.7.0
 *
 */
class WP_Http_Unit_Test_Transport implements Http_Transport {

	/**
	 * @var array<array{url: string, args: array}>
	 */
	protected static $requests_sent = [];

	/**
	 * @var array<string, mixed|callable>
	 */
	protected static $mocked_responses = [];


	/**
	 * Return the results of a request without actually making it.
	 *
	 * - If a mock is provided, response will be used.
	 * - If a mock is not provided, an empty response will be used.
	 * - The request is store for later comparison.
	 *
	 * @internal
	 *
	 * @param string $url  - URL being requested.
	 * @param array  $args - Information about the request like headers and data.
	 *
	 * @return string
	 */
	public function request( string $url, ...$args ): string {
		self::$requests_sent[] = [
			'url'  => $url,
			'args' => $args,
		];

		$mock = null;
		if ( isset( self::$mocked_responses[ $url ] ) ) {
			$mock = self::$mocked_responses[ $url ];
		} elseif ( isset( self::$mocked_responses[ untrailingslashit( $url ) ] ) ) {
			// WP will add a trailing slash to the URL internally, so we need to check for both.
			$mock = self::$mocked_responses[ untrailingslashit( $url ) ];
		}
		if ( null !== $mock ) {
			if ( \is_callable( $mock ) ) {
				return \call_user_func( $mock, $args );
			}
			return $mock;
		}

		if ( isset( $args[0]['Accept'] ) && false !== \strpos( $args[0]['Accept'], 'application/json' ) ) {
			return Requests::instance()->json_response( [] );
		}

		return Requests::instance()->html_response( '' );
	}


	/**
	 * Only store the request, don't actually do anything.
	 *
	 * @internal
	 *
	 * @param array $requests
	 *
	 * @return array
	 */
	public function request_multiple( array $requests ): array {
		$return = [];
		foreach ( $requests as $request ) {
			$return[] = $this->request( $request['url'], $request );
		}
		return $return;
	}


	/**
	 * Mock the returned raw response based on a URL.
	 *
	 * @param string         $url
	 * @param mixed|callable $response
	 */
	public static function add_mock( string $url, $response ) {
		self::$mocked_responses[ $url ] = $response;
	}


	/**
	 * Decorator to return the information from a request.
	 *
	 * @param int $index Optional. Array index of mock_sent value.
	 *
	 * @return object{url: string, args: array}|false
	 */
	public static function get_sent( $index = 0 ) {
		$retrieval = false;
		if ( isset( self::$requests_sent[ $index ] ) ) {
			$retrieval = (object) self::$requests_sent[ $index ];
		}
		return $retrieval;
	}


	/**
	 * Clear all mocks between runs.
	 */
	public static function clear_mocks(): void {
		self::$mocked_responses = [];
		self::$requests_sent = [];
	}


	/**
	 * @return array{mocked: array<string, mixed|callable>, sent: array<array{url: string, args: array}>
	 */
	public static function get_mocks(): array {
		return [
			'mocked' => self::$mocked_responses,
			'sent'   => self::$requests_sent,
		];
	}


	/**
	 * Use by \WP_Http::_get_first_available_transport to determine
	 * this transport may be used.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public static function test(): bool {
		return true;
	}
}
