<?php

/**
 * Test remote requests without actually sending them.
 *
 * Results of requests may be mocked or simply recorded to see if the request
 * was sent.
 *
 * No requests will go out within the confines of this test.
 *
 * - Use to compare requests being sent.
 * - Use to mock raw responses using one of the "format_" methods.
 *
 * @notice If you are not testing the requests themselves and simply want to
 *         change the resulting response, you probably want to mock your method
 *         sending the request instead of using this class.
 *
 * @author Mat Lipe
 * @since  1.10.0
 *
 */
class WP_Http_Remote_Post_TestCase extends WP_UnitTestCase {
	protected static $mock_sent = [];

	protected static $mock_response = [];


	/**
	 * The `Requests` class was deprecated in WP 6.2 in favor
	 * of a `\WpOrg\Requests\Requests` class.
	 *
	 * This method translates the use to the appropriate class
	 * based on availability.
	 *
	 * @todo Remove in favor of simple `use` at the top of the file
	 *       when WP 6.2 becomes the minimum.
	 *
	 * @phpstan-return class-string
	 *
	 * @return string
	 */
	protected function get_request_class() : string {
		if ( class_exists( \WpOrg\Requests\Requests::class ) ) {
			return \WpOrg\Requests\Requests::class;
		}
		return Requests::class;
	}


	public function set_up() {
		parent::set_up();
		putenv( 'PHP_MOCKED_REMOTE_POST=1' );
		$this->get_request_class()::$transport[ serialize( [] ) ] = __CLASS__;
		$this->get_request_class()::$transport[ serialize( [ 'ssl' => false ] ) ] = __CLASS__;
		$this->get_request_class()::$transport[ serialize( [ 'ssl' => true ] ) ] = __CLASS__;
		add_filter( 'http_api_transports', [ $this, 'use_this_class_for_transport' ], 99 );
	}


	public function tear_down() {
		self::$mock_sent = [];
		self::$mock_response = [];
		putenv( 'PHP_MOCKED_REMOTE_POST' );
		$this->get_request_class()::$transport = [];
		remove_filter( 'http_api_transports', [ $this, 'use_this_class_for_transport' ], 99 );
		parent::tear_down();
	}


	/**
	 * Decorator to return the information for a sent mock.
	 *
	 * @param int $index Optional. Array index of mock_sent value.
	 *
	 *
	 * @return object
	 */
	public function get_sent( $index = 0 ) {
		$retrieval = false;
		if ( isset( self::$mock_sent[ $index ] ) ) {
			$retrieval = (object) self::$mock_sent[ $index ];
		}
		return $retrieval;
	}


	/**
	 * Mock the returned raw response based on a URL.
	 *
	 * @param string         $url
	 * @param mixed|callable $callback_or_value
	 */
	public function mock_response( string $url, $callback_or_value ) {
		self::$mock_response[ $url ] = $callback_or_value;
	}


	/**
	 * Convert JSON data into a raw request response.
	 *
	 * For use when mocking responses.
	 *
	 * @see WP_Http_Remote_Post_TestCase::mock_response()
	 *
	 * @param array|JsonSerializable $data
	 *
	 * @return string
	 */
	public function format_json_response( $data ) : string {
		return 'HTTP/1.1 200 OK
				Content-Type: application/json; charset=UTF-8'
		       . "\r\n\r\n" .
		       wp_json_encode( $data );
	}


	/**
	 * Convert HTML string into a raw request response.
	 *
	 * For use when mocking responses.
	 *
	 * @see WP_Http_Remote_Post_TestCase::mock_response()
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function format_html_response( string $html ) : string {
		return 'HTTP/1.1 200 OK
			   Content-Type: text/html; charset=UTF-8'
		       . "\r\n\r\n" .
		       '<!DOCTYPE html />' . $html;
	}


	/**
	 * @internal
	 *
	 * @return string[]
	 */
	public function use_this_class_for_transport() : array {
		return [ 'Remote_Post_TestCase' ];
	}


	/**
	 * Only store the request, don't actually do anything.
	 *
	 * @see \WpOrg\Requests\Transport\Curl::request for example response.
	 *
	 * @internal
	 *
	 * @param array  $args
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function request( $url, ...$args ) {
		self::$mock_sent[] = [
			'url'  => $url,
			'args' => $args,
		];

		if ( isset( self::$mock_response[ $url ] ) ) {
			if ( is_callable( self::$mock_response[ $url ] ) ) {
				return call_user_func( self::$mock_response[ $url ], $args );
			}
			return self::$mock_response[ $url ];
		}

		return 'HTTP/1.1 200 OK
				Server: Apache
				Content-Type: text/html; charset=UTF-8

				<!DOCTYPE html />';
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
	public function request_multiple( array $requests ) : array {
		$return = [];
		foreach ( $requests as $request ) {
			$return[] = $this->request( $request['url'], $request );
		}
		return $return;
	}


	/**
	 * Use by \WP_Http::_get_first_available_transport to determine
	 * this transport may be used.
	 *
	 * PHPUnit will think this is a test method, but we can't rename it.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public static function test() {
		self::assertTrue( true ); // Do nothing silently.
		return true;
	}

}
