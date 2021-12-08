<?php
/**
 * WP_REST_Edit_Site_Export_Controller tests.
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 5.9.0
 */

/**
 * Tests for WP_REST_Edit_Site_Export_Controller.
 *
 * @since 5.9.0
 *
 * @covers WP_REST_Edit_Site_Export_Controller
 *
 * @group restapi
 */
class Tests_REST_WpRestEditSiteExportController extends WP_Test_REST_Controller_Testcase {

	/**
	 * The REST API route for the edit site export.
	 *
	 * @since 5.9.0
	 *
	 * @var string
	 */
	const REQUEST_ROUTE = '/wp-block-editor/v1/export';

	/**
	 * Subscriber user ID.
	 *
	 * @since 5.9.0
	 *
	 * @var int
	 */
	protected static $subscriber_id;

	/**
	 * Set up class test fixtures.
	 *
	 * @since 5.9.0
	 *
	 * @param WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$subscriber_id = $factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
	}

	/**
	 * Delete test data after our tests run.
	 *
	 * @since 5.9.0
	 */
	public static function wpTearDownAfterClass() {
		self::delete_user( self::$subscriber_id );
	}

	/**
	 * @covers WP_REST_Edit_Site_Export_Controller::register_routes
	 * @ticket 54448
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( static::REQUEST_ROUTE, $routes );
		$this->assertCount( 1, $routes[ static::REQUEST_ROUTE ] );
	}

	/**
	 * @covers WP_REST_Edit_Site_Export_Controller::permissions_check
	 *
	 * @ticket 54448
	 */
	public function test_export_for_no_user_permissions() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', static::REQUEST_ROUTE );
		$response = rest_get_server()->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_export_templates', $response, 401 );
	}

	/**
	 * @covers WP_REST_Edit_Site_Export_Controller::permissions_check
	 *
	 * @ticket 54448
	 */
	public function test_export_for_user_with_insufficient_permissions() {
		wp_set_current_user( self::$subscriber_id );

		$request  = new WP_REST_Request( 'GET', static::REQUEST_ROUTE );
		$response = rest_get_server()->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_export_templates', $response, 403 );
	}

	/**
	 * @ticket 54448
	 */
	public function test_context_param() {
		$this->markTestSkipped( 'Controller does not implement context_param().' );
	}

	/**
	 * @ticket 54448
	 */
	public function test_get_item() {
		$this->markTestSkipped( 'Controller does not implement get_item().' );
	}

	/**
	 * @ticket 54448
	 */
	public function test_get_items() {
		$this->markTestSkipped( 'Controller does not implement get_items().' );
	}

	/**
	 * @ticket 54448
	 */
	public function test_create_item() {
		$this->markTestSkipped( 'Controller does not implement create_item().' );
	}

	/**
	 * @ticket 54448
	 */
	public function test_update_item() {
		$this->markTestSkipped( 'Controller does not implement update_item().' );
	}

	/**
	 * @ticket 54448
	 */
	public function test_delete_item() {
		$this->markTestSkipped( 'Controller does not implement delete_item().' );
	}

	/**
	 * @ticket 54448
	 */
	public function test_prepare_item() {
		$this->markTestSkipped( 'Controller does not implement prepare_item().' );
	}

	/**
	 * @ticket 54448
	 */
	public function test_get_item_schema() {
		$this->markTestSkipped( 'Controller does not implement get_item_schema().' );
	}
}
