<?php

use Lipe\WP_Unit\Utils\PrivateAccess;
use WpOrg\Requests\Requests;

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
 * @see \Lipe\WP_Unit\Utils\Requests for formatting responses.
 *
 * @phpstan-import-type SENT from WP_Http_Unit_Test_Transport
 */
class WP_Http_Remote_Post_TestCase extends WP_UnitTestCase {
	public static function set_up_before_class() {
		parent::set_up_before_class();
		\putenv( 'PHP_MOCKED_REMOTE_POST=1' );
		Requests::$transport[ serialize( [] ) ] = WP_Http_Unit_Test_Transport::class;
		Requests::$transport[ serialize( [ 'ssl' => false ] ) ] = WP_Http_Unit_Test_Transport::class;
		Requests::$transport[ serialize( [ 'ssl' => true ] ) ] = WP_Http_Unit_Test_Transport::class;

		// Pre WP 6.4.
		if ( ! defined( Requests::class . "::DEFAULT_TRANSPORTS" ) ) {
			add_filter( 'http_api_transports', [ self::class, 'use_this_class_for_transport' ], 99 );
		} else {
			PrivateAccess::in()->set_private_property( Requests::class, 'transports', [
				WP_Http_Unit_Test_Transport::class => WP_Http_Unit_Test_Transport::class
			] );
		}
	}


	public static function tear_down_after_class() {
		putenv( 'PHP_MOCKED_REMOTE_POST' );
		Requests::$transport = [];

		// Pre WP 6.4.
		if ( ! defined( Requests::class . '::DEFAULT_TRANSPORTS' ) ) {
			remove_filter( 'http_api_transports', [ self::class, 'use_this_class_for_transport' ], 99 );
		} else {
			// Restore the default transports.
			Requests::$transport = [];
			PrivateAccess::in()->set_private_property( Requests::class, 'transports', Requests::DEFAULT_TRANSPORTS );
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
	 * @return SENT[]
	 */
	public function get_all_sent() {
		return \WP_Http_Unit_Test_Transport::get_mocks()['sent'];
	}

	/**
	 * Mock the returned raw response based on a URL.
	 *
	 * @see \Lipe\WP_Unit\Utils\Requests for formatting responses.
	 *
	 * @param string         $url
	 * @param string|(callable(array<string, mixed>): string) $callback_or_value
	 */
	public function mock_response( string $url, $callback_or_value ) {
		\WP_Http_Unit_Test_Transport::add_mock( $url, $callback_or_value );
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
