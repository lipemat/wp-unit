<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Framework;

use Lipe\WP_Unit\Utils\Requests;

/**
 * @author Mat Lipe
 * @since  March 2024
 *
 */
class WP_Http_Unit_Test_TransportTest extends \WP_Http_Remote_Post_TestCase {
	private static $run_in_correct_order = false;


	public static function tear_down_after_class() {
		self::assertCount( 3, \WpOrg\Requests\Requests::$transport );
		self::assertSame( '1', getenv( 'PHP_MOCKED_REMOTE_POST' ) );

		parent::tear_down_after_class();
		self::assertCount( 0, \WpOrg\Requests\Requests::$transport );
		self::assertFalse( getenv( 'PHP_MOCKED_REMOTE_POST' ) );
		self::assertFalse( has_filter( 'http_api_transports' ) );
	}


	public function test_get_sent(): void {
		$this->mock_response( 'https://example.com', Requests::instance()->html_response( 'test' ) );
		$response = wp_remote_post( 'https://example.com', [] );
		$this->assertSame( 'https://example.com/', $this->get_sent()->url );
		$this->assertSame( '<!DOCTYPE html />test', wp_remote_retrieve_body( $response ) );

		$this->mock_response( 'https://other.com/?url-param=true&second=false', Requests::instance()->json_response( [ 'json' => 'is complex' ] ) );
		$response = wp_remote_get( 'https://other.com/?url-param=true&second=false', [
			'headers' => [
				'Accept' => 'application/json',
			],
		] );
		$this->assertSame( 'https://other.com/?url-param=true&second=false', $this->get_sent( 1 )->url );
		$this->assertSame( [ 'json' => 'is complex' ], json_decode( wp_remote_retrieve_body( $response ), true ) );

		$this->assertCount( 2, \WP_Http_Unit_Test_Transport::get_mocks()['mocked'] );
		$this->assertCount( 2, \WP_Http_Unit_Test_Transport::get_mocks()['sent'] );
		$this->assertSame( static::$mock_sent, \WP_Http_Unit_Test_Transport::get_mocks()['sent'] );
		$this->assertSame( static::$mock_response, \WP_Http_Unit_Test_Transport::get_mocks()['mocked'] );

		$this->assertMatchesSnapshot( \WP_Http_Unit_Test_Transport::get_mocks()['mocked'] );

		self::$run_in_correct_order = true;
	}


	/**
	 * @depends test_get_sent
	 */
	public function test_clear_mocks(): void {
		$this->assertTrue( self::$run_in_correct_order );
		$this->assertCount( 0, \WP_Http_Unit_Test_Transport::get_mocks()['mocked'] );
		$this->assertCount( 0, \WP_Http_Unit_Test_Transport::get_mocks()['sent'] );
	}
}
