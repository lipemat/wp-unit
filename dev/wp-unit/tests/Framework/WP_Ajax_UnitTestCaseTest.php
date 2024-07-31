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
		$this->assertSame( '', $result );
	}
}
