<?php

/**
 * Test remote requests without actually sending them.
 *
 * Tests the sending of the request, not the results.
 *
 * @notice If you need to test the application based on data received from
 *         a remote request it's better to mock the method, which send the
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


	/**
	 * The `Requests` class was deprecated in WP 6.2 in favor
	 * of a `\WpOrg\Requests\Requests` class.
	 *
	 * This method translates the use to the appropriate class
	 * based on availability.
	 *
	 * @todo Remove in favor of simple `use` at the top of the file
	 *       when WP 6.2 becomes the minimum.
	 * @return string
	 */
	protected function get_request_class() : string {
		if ( class_exists( \WpOrg\Requests\Requests::class )) {
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
	 * @see \WpOrg\Requests\Transport\Curl::request for example response.
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
	 * PHPUnit will think this is a test method but we can't rename it.
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
