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
	/**
	 * @deprecated Use \WP_Http_Unit_Test_Transport::get_mocks() instead.
	 */
	public static $mock_sent = [];

	/**
	 * @deprecated Use \WP_Http_Unit_Test_Transport::get_mocks() instead.
	 */
	public static $mock_response = [];


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
	 * @deprecated DO NOT USE
	 *
	 * @return class-string
	 */
	protected static function get_request_class() : string {
		if ( class_exists( \WpOrg\Requests\Requests::class ) ) {
			return \WpOrg\Requests\Requests::class;
		}
		return Requests::class;
	}


	public static function set_up_before_class() {
		parent::set_up_before_class();
		putenv( 'PHP_MOCKED_REMOTE_POST=1' );
		self::get_request_class()::$transport[ serialize( [] ) ] = WP_Http_Unit_Test_Transport::class;
		self::get_request_class()::$transport[ serialize( [ 'ssl' => false ] ) ] = WP_Http_Unit_Test_Transport::class;
		self::get_request_class()::$transport[ serialize( [ 'ssl' => true ] ) ] = WP_Http_Unit_Test_Transport::class;

		// Pre WP 6.4.
		if ( ! defined( self::get_request_class() . "::DEFAULT_TRANSPORTS" ) ) {
			add_filter( 'http_api_transports', [ self::class, 'use_this_class_for_transport' ], 99 );
		} else {
			// Swap out the transports with only our mock.
			$reflection = new \ReflectionClass( self::get_request_class() );
			$reflectionProperty = $reflection->getProperty( 'transports' );
			$reflectionProperty->setAccessible( true );
			$reflectionProperty->setValue( null, [
				WP_Http_Unit_Test_Transport::class => WP_Http_Unit_Test_Transport::class
			] );
		}
	}


	public static function tear_down_after_class() {
		putenv( 'PHP_MOCKED_REMOTE_POST' );
		self::get_request_class()::$transport = [];

		// Pre WP 6.4.
		if ( ! defined( self::get_request_class() . '::DEFAULT_TRANSPORTS' ) ) {
			remove_filter( 'http_api_transports', [ self::class, 'use_this_class_for_transport' ], 99 );
		} else {
			// Restore the default transports.
			self::get_request_class()::$transport = [];
			$reflection = new \ReflectionClass( self::get_request_class());
			$reflectionProperty = $reflection->getProperty( 'transports' );
			$reflectionProperty->setAccessible( true );
			$reflectionProperty->setValue( null, self::get_request_class()::DEFAULT_TRANSPORTS );
		}

		parent::tear_down_after_class();
	}


	public function tear_down() {
		\WP_Http_Unit_Test_Transport::clear_mocks();

		parent::tear_down();
	}


	/**
	 * Decorator to return the information from a request.
	 *
	 * @param int $index Optional. Array index of mock_sent value.
	 *
	 * @return object{url: string, args: array}|false
	 */
	public function get_sent( $index = 0 ) {
		return \WP_Http_Unit_Test_Transport::get_sent( $index );
	}


	/**
	 * Mock the returned raw response based on a URL.
	 *
	 * @param string         $url
	 * @param mixed|callable $callback_or_value
	 */
	public function mock_response( string $url, $callback_or_value ) {
		\WP_Http_Unit_Test_Transport::add_mock( $url, $callback_or_value );
	}


	/**
	 * @deprecated In favor of `\Lipe\WP_Unit\Utils\Requests::format_json_response`
	 */
	public function format_json_response( $data ): string {
		return \Lipe\WP_Unit\Utils\Requests::instance()->json_response( $data );
	}


	/**
	 * @deprecated In favor of `\Lipe\WP_Unit\Utils\Requests::format_html_response`
	 */
	public function format_html_response( string $html ): string {
		return \Lipe\WP_Unit\Utils\Requests::instance()->html_response( $html );
	}


	/**
	 * Before WP 6.4, we need to filter the transports class for the requests to use it.
	 *
	 * WP will automatically add "WP_Http_" to the class name, so it must exist in the global
	 * namespace in that format.
	 *
	 * @see WP_Http_Unit_Test_Transport
	 *
	 * @deprecated DO NOT USE
	 *
	 * @return string[]
	 */
	public static function use_this_class_for_transport(): array {
		return [ 'Unit_Test_Transport' ];
	}
}
