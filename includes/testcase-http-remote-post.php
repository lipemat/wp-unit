<?php

/**
 * Test remote requests without actually sending them.
 *
 * Tests the send of the request, not the results.
 *
 * @notice If you need to test the application based on data received from
 *         a remote request it's better to mock the method which send the
 *         request then to use this class because this class only supports
 *         mocking raw response data.
 *         If you want to be sure requests are being sent but are not doing
 *         anything meaningful with the response, use this class.
 *
 * @author Mat Lipe
 * @since  1.10.0
 *
 */
class WP_Http_Remote_Post_TestCase extends WP_UnitTestCase {
	protected static $mock_sent = [];

	protected static $mock_response = [];


	public function setUp() {
		parent::setUp();
		self::$mock_sent = [];
		self::$mock_response = [];
		putenv( 'PHP_MOCKED_REMOTE_POST=1' );
		Requests::$transport[ serialize( [] ) ] = __CLASS__;
		Requests::$transport[ serialize( [ 'ssl' => false ] ) ] = __CLASS__;
		Requests::$transport[ serialize( [ 'ssl' => true ] ) ] = __CLASS__;
		add_filter( 'http_api_transports', [ $this, 'use_this_class_for_transport' ] );
	}


	public function tearDown() {
		putenv( 'PHP_MOCKED_REMOTE_POST' );
		Requests::$transport = [];
		return parent::tearDown();
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
	 * @param string $url
	 * @param        $callback_or_value
	 */
	public function mock_response( $url, $callback_or_value ) {
		self::$mock_response[ $url ] = $callback_or_value;
	}


	/**
	 * @internal
	 *
	 * @return string[]
	 */
	public function use_this_class_for_transport() {
		return [ 'Remote_Post_TestCase' ];
	}


	/**
	 * Only store the request, don't actually do anything.
	 *
	 * @param string $url
	 * @param array  $args
	 *
	 * @internal
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
	 * @param array $requests
	 * @param array $options
	 *
	 * @internal
	 *
	 * @return array
	 */
	public function request_multiple( $requests, $options ) {
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
	 * @internal
	 *
	 * @return bool
	 */
	public static function test() {
		return true;
	}

}
