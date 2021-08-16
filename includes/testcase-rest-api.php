<?php

/**
 * Testcase for REST endpoints.
 *
 */
abstract class WP_Test_REST_TestCase extends WP_UnitTestCase {
	/**
	 * Checks if the response is a WP_Error or a WP_REST_Response
	 * which includes an error within.
	 *
	 * Verifies provided code matche error code.
	 *
	 * @param      $code
	 * @param      $response
	 * @param null $status
	 */
	protected function assertErrorResponse( $code, $response, $status = null ) {
		if ( is_a( $response, 'WP_REST_Response' ) ) {
			$response = $response->as_error();
		}

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( $code, $response->get_error_code() );

		if ( null !== $status ) {
			$data = $response->get_error_data();
			$this->assertArrayHasKey( 'status', $data );
			$this->assertEquals( $status, $data['status'] );
		}
	}


	/**
	 * Checks if the response is not a WP_Error nor a WP_REST_Response
	 * which includes an error within.
	 *
	 * @param $response
	 */
	protected function assertNotErrorResponse( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		if ( is_a( $response, 'WP_REST_Response' ) ) {
			$this->assertNull( $response->as_error() );
		}
	}


	/**
	 * Mock a REST request and retrieve its response.
	 *
	 * @param string                $route  - e.g. /wp/v2/users
	 * @param array                 $args   - Query parameters to pass.
	 * @param GET|POST|PATCH|DELETE $method - Request method
	 *
	 * @return WP_REST_Response
	 */
	protected function get_response( $route, array $args, $method = 'POST' ) {
		$request = new \WP_REST_Request( $method, $route );
		$request->set_query_params( $args );
		return rest_get_server()->dispatch( $request );
	}
}
