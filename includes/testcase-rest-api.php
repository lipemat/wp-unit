<?php

/**
 * Testcase for REST endpoints.
 *
 */
abstract class WP_Test_REST_TestCase extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		// Ensure permission checks get a fresh user.
		$GLOBALS['current_user'] = null;
	}


	/**
	 * Clear any changes made to the global wp_rest_server when
	 * a test is done.
	 *
	 * @see rest_get_server
	 *
	 * @since 3.5.1
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $GLOBALS['wp_rest_server'] );
		$GLOBALS['current_user'] = null;
		parent::tear_down();
	}


	/**
	 * Checks if the response is a WP_Error or a WP_REST_Response
	 * which includes an error within.
	 *
	 * Verifies provided code matche error code.
	 *
	 * @since 4.3.0 -- Allow validating a response message.
	 *
	 * @param int|string                  $code     - Error code. (e.g. 'rest_forbidden')
	 * @param \WP_REST_Response|\WP_Error $response - Response from REST request.
	 * @param ?int                        $status   - HTTP status code.
	 * @param ?string                     $message  - Error message.
	 *
	 */
	protected function assertErrorResponse( $code, $response, ?int $status = null, ?string $message = null ) {
		if ( $response instanceof WP_REST_Response ) {
			$response = $response->as_error();
		}

		$this->assertWPError( $response );
		$this->assertSame( $code, $response->get_error_code() );

		if ( null !== $status ) {
			$data = $response->get_error_data();
			$this->assertArrayHasKey( 'status', $data );
			$this->assertSame( $status, $data['status'] );
		}
		if ( null !== $message ) {
			$this->assertSame( $message, $response->get_error_message() );
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
		if ( \is_a( $response, 'WP_REST_Response' ) ) {
			$this->assertNull( $response->as_error() );
		}
	}


	/**
	 * Mock a REST request and retrieve its response.
	 *
	 * @param string                                    $route  - e.g. /wp/v2/users
	 * @param array                                     $args   - Query parameters to pass.
	 * @param 'GET'|'POST'|'PATCH'|'PUT'|'DELETE'|'URL' $method - Request method
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_response( string $route, array $args, string $method = 'POST' ) {
		$this->start_request();

		$request = new \WP_REST_Request( $method, $route );
		switch ( strtoupper( $method ) ) {
			case 'URL':
				$request->set_url_params( $args );
				break;
			case 'GET':
				$request->set_query_params( $args );
				break;
			default:
				$request->set_body_params( $args );
				break;
		}
		$result = rest_get_server()->dispatch( $request );

		$this->end_request();
		return $result;
	}


	/**
	 * Enable the context of a REST request before dispatching it.
	 *
	 * May be overridden by subclasses to enable additional contexts.
	 *
	 * @see WP_Test_REST_TestCase::end_request()
	 *
	 * @return void
	 */
	protected function start_request(): void {
		add_filter( 'application_password_is_api_request', [$this, '_return_true' ], 0 );
		add_filter( 'wp_is_rest_endpoint', [ $this, '_return_true' ], 0 );
	}


	/**
	 * Cleanup anything added during `start_request`.
	 *
	 * @see WP_Test_REST_TestCase::start_request()
	 *
	 * @return void
	 */
	protected function end_request(): void {
		remove_filter( 'application_password_is_api_request', [ $this, '_return_true' ], 0 );
		remove_filter( 'wp_is_rest_endpoint', [ $this, '_return_true' ], 0 );
	}


	/**
	 * Use our own return true function to avoid conflicts with other plugins.
	 * @internal
	 */
	public function _return_true(): bool {
		return true;
	}
}
