<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Framework;

/**
 * @author Mat Lipe
 * @since  July 2024
 *
 */
class WP_Ajax_UnitTestCaseTest extends \WP_Ajax_UnitTestCase {
	public function test_request_args_are_preserved(): void {
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'test-nonce' );
		$result = $this->_getResult( function() {
			check_ajax_referer( 'test-nonce' );
			wp_send_json_success( 'test' );
		} );
		$this->assertEquals( '{"success":true,"data":"test"}', $result );

		unset( $_REQUEST['_ajax_nonce'] );
		$result = $this->_getResult( function() {
			check_ajax_referer( 'test-nonce' );
			wp_send_json_success( 'test' );
		} );
		$this->assertSame( '-1', $result );
	}


	public function test_get_result_distinct_array_callables(): void {
		$this->assertSame( '{"success":true,"data":"first"}', $this->_getResult( [ self::class, 'send_first' ] ) );
		$this->assertSame( '{"success":true,"data":"second"}', $this->_getResult( [ self::class, 'send_second' ] ) );
	}


	public function test_get_result_distinct_string_callables(): void {
		$this->assertSame( '{"success":true,"data":"first"}', $this->_getResult( self::class . '::send_first' ) );
		$this->assertSame( '{"success":true,"data":"second"}', $this->_getResult( self::class . '::send_second' ) );
	}


	public static function send_first(): void {
		wp_send_json_success( 'first' );
	}


	public static function send_second(): void {
		wp_send_json_success( 'second' );
	}
}
