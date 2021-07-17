<?php

abstract class WP_Test_REST_TestCase extends WP_UnitTestCase {
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


	protected function assertNotErrorResponse( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
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
	protected function get_response( string $route, array $args, string $method = 'POST' ) : WP_REST_Response {
		$request = new \WP_REST_Request( $method, $route );
		$request->set_query_params( $args );
		return rest_get_server()->dispatch( $request );
	}
}
