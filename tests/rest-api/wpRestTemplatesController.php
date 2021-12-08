<?php
/**
 * Unit tests covering the templates endpoint..
 *
 * @package WordPress
 * @subpackage REST API
 */

/**
 * Tests for REST API for templates.
 *
 * @covers WP_REST_Templates_Controller
 *
 * @group restapi
 */
class Tests_REST_WpRestTemplatesController extends WP_Test_REST_Controller_Testcase {
	/**
	 * @var int
	 */
	protected static $admin_id;
	private static $post;

	/**
	 * Create fake data before our tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Helper that lets us create fake data.
	 */
	public static function wpSetupBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Set up template post.
		$args       = array(
			'post_type'    => 'wp_template',
			'post_name'    => 'my_template',
			'post_title'   => 'My Template',
			'post_content' => 'Content',
			'post_excerpt' => 'Description of my template.',
			'tax_input'    => array(
				'wp_theme' => array(
					get_stylesheet(),
				),
			),
		);
		self::$post = self::factory()->post->create_and_get( $args );
		wp_set_post_terms( self::$post->ID, get_stylesheet(), 'wp_theme' );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$post->ID );
	}

	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wp/v2/templates', $routes );
		$this->assertArrayHasKey( '/wp/v2/templates/(?P<id>[\/\w-]+)', $routes );
	}

	/**
	 * @covers WP_REST_Templates_Controller::get_context_param
	 */
	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/templates' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertSame( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/templates/default//my_template' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertSame( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	/**
	 * @covers WP_REST_Templates_Controller::get_items
	 */
	public function test_get_items() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/templates' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame(
			array(
				'id'             => 'default//my_template',
				'theme'          => 'default',
				'slug'           => 'my_template',
				'source'         => 'custom',
				'origin'         => null,
				'type'           => 'wp_template',
				'description'    => 'Description of my template.',
				'title'          => array(
					'raw'      => 'My Template',
					'rendered' => 'My Template',
				),
				'status'         => 'publish',
				'wp_id'          => self::$post->ID,
				'has_theme_file' => false,
				'is_custom'      => true,
				'author'         => 0,
			),
			$this->find_and_normalize_template_by_id( $data, 'default//my_template' )
		);
	}

	/**
	 * @covers WP_REST_Templates_Controller::get_items
	 */
	public function test_get_items_no_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/templates' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_manage_templates', $response, 401 );
	}

	/**
	 * @covers WP_REST_Templates_Controller::get_item
	 */
	public function test_get_item() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/templates/default//my_template' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		unset( $data['content'] );
		unset( $data['_links'] );

		$this->assertSame(
			array(
				'id'             => 'default//my_template',
				'theme'          => 'default',
				'slug'           => 'my_template',
				'source'         => 'custom',
				'origin'         => null,
				'type'           => 'wp_template',
				'description'    => 'Description of my template.',
				'title'          => array(
					'raw'      => 'My Template',
					'rendered' => 'My Template',
				),
				'status'         => 'publish',
				'wp_id'          => self::$post->ID,
				'has_theme_file' => false,
				'is_custom'      => true,
				'author'         => 0,
			),
			$data
		);
	}

	/**
	 * @ticket 54507
	 * @dataProvider get_template_endpoint_urls
	 */
	public function test_get_item_works_with_a_single_slash( $endpoint_url ) {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', $endpoint_url );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		unset( $data['content'] );
		unset( $data['_links'] );

		$this->assertSame(
			array(
				'id'             => 'default//my_template',
				'theme'          => 'default',
				'slug'           => 'my_template',
				'source'         => 'custom',
				'origin'         => null,
				'type'           => 'wp_template',
				'description'    => 'Description of my template.',
				'title'          => array(
					'raw'      => 'My Template',
					'rendered' => 'My Template',
				),
				'status'         => 'publish',
				'wp_id'          => self::$post->ID,
				'has_theme_file' => false,
				'is_custom'      => true,
				'author'         => 0,
			),
			$data
		);
	}

	public function get_template_endpoint_urls() {
		return array(
			array( '/wp/v2/templates/default/my_template' ),
			array( '/wp/v2/templates/default//my_template' ),
		);
	}

	/**
	 * @ticket 54507
	 * @dataProvider get_template_ids_to_sanitize
	 */
	public function test_sanitize_template_id( $input_id, $sanitized_id ) {
		$endpoint = new WP_REST_Templates_Controller( 'wp_template' );
		$this->assertEquals(
			$sanitized_id,
			$endpoint->_sanitize_template_id( $input_id )
		);
	}

	public function get_template_ids_to_sanitize() {
		return array(
			array( 'tt1-blocks/index', 'tt1-blocks//index' ),
			array( 'tt1-blocks//index', 'tt1-blocks//index' ),

			array( 'theme-experiments/tt1-blocks/index', 'theme-experiments/tt1-blocks//index' ),
			array( 'theme-experiments/tt1-blocks//index', 'theme-experiments/tt1-blocks//index' ),
		);
	}

	/**
	 * @ticket 54422
	 * @covers WP_REST_Templates_Controller::create_item
	 */
	public function test_create_item() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/templates' );
		$request->set_body_params(
			array(
				'slug'        => 'my_custom_template',
				'description' => 'Just a description',
				'title'       => 'My Template',
				'content'     => 'Content',
				'author'      => self::$admin_id,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		unset( $data['_links'] );
		unset( $data['wp_id'] );

		$this->assertSame(
			array(
				'id'             => 'default//my_custom_template',
				'theme'          => 'default',
				'content'        => array(
					'raw' => 'Content',
				),
				'slug'           => 'my_custom_template',
				'source'         => 'custom',
				'origin'         => null,
				'type'           => 'wp_template',
				'description'    => 'Just a description',
				'title'          => array(
					'raw'      => 'My Template',
					'rendered' => 'My Template',
				),
				'status'         => 'publish',
				'has_theme_file' => false,
				'is_custom'      => true,
				'author'         => self::$admin_id,
			),
			$data
		);
	}

	/**
	 * @ticket 54422
	 * @covers WP_REST_Templates_Controller::create_item
	 */
	public function test_create_item_raw() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/templates' );
		$request->set_body_params(
			array(
				'slug'        => 'my_custom_template_raw',
				'description' => 'Just a description',
				'title'       => array(
					'raw' => 'My Template',
				),
				'content'     => array(
					'raw' => 'Content',
				),
				'author'      => self::$admin_id,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		unset( $data['_links'] );
		unset( $data['wp_id'] );

		$this->assertSame(
			array(
				'id'             => 'default//my_custom_template_raw',
				'theme'          => 'default',
				'content'        => array(
					'raw' => 'Content',
				),
				'slug'           => 'my_custom_template_raw',
				'source'         => 'custom',
				'origin'         => null,
				'type'           => 'wp_template',
				'description'    => 'Just a description',
				'title'          => array(
					'raw'      => 'My Template',
					'rendered' => 'My Template',
				),
				'status'         => 'publish',
				'has_theme_file' => false,
				'is_custom'      => true,
				'author'         => self::$admin_id,
			),
			$data
		);
	}

	public function test_create_item_invalid_author() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/templates' );
		$request->set_body_params(
			array(
				'slug'        => 'my_custom_template_invalid_author',
				'description' => 'Just a description',
				'title'       => 'My Template',
				'content'     => 'Content',
				'author'      => -1,
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_author', $response, 400 );
	}

	/**
	 * @covers WP_REST_Templates_Controller::update_item
	 */
	public function test_update_item() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/templates/default//my_template' );
		$request->set_body_params(
			array(
				'title' => 'My new Index Title',
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'My new Index Title', $data['title']['raw'] );
		$this->assertSame( 'custom', $data['source'] );
	}

	/**
	 * @covers WP_REST_Templates_Controller::update_item
	 */
	public function test_update_item_raw() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/templates/default//my_template' );
		$request->set_body_params(
			array(
				'title' => array( 'raw' => 'My new raw Index Title' ),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'My new raw Index Title', $data['title']['raw'] );
		$this->assertSame( 'custom', $data['source'] );
	}

	/**
	 * @covers WP_REST_Templates_Controller::delete_item
	 */
	public function test_delete_item() {
		// Set up template post.
		$args    = array(
			'post_type'    => 'wp_template',
			'post_name'    => 'my_test_template',
			'post_title'   => 'My Template',
			'post_content' => 'Content',
			'post_excerpt' => 'Description of my template.',
			'tax_input'    => array(
				'wp_theme' => array(
					get_stylesheet(),
				),
			),
		);
		$post_id = self::factory()->post->create( $args );
		wp_set_post_terms( $post_id, get_stylesheet(), 'wp_theme' );

		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'DELETE', '/wp/v2/templates/default//my_test_template' );
		$request->set_param( 'force', 'false' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'My Template', $data['title']['raw'] );
		$this->assertSame( 'trash', $data['status'] );
	}

	/**
	 * @covers WP_REST_Templates_Controller::delete_item
	 */
	public function test_delete_item_skip_trash() {
		// Set up template post.
		$args    = array(
			'post_type'    => 'wp_template',
			'post_name'    => 'my_test_template',
			'post_title'   => 'My Template',
			'post_content' => 'Content',
			'post_excerpt' => 'Description of my template.',
			'tax_input'    => array(
				'wp_theme' => array(
					get_stylesheet(),
				),
			),
		);
		$post_id = self::factory()->post->create( $args );
		wp_set_post_terms( $post_id, get_stylesheet(), 'wp_theme' );

		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'DELETE', '/wp/v2/templates/default//my_test_template' );
		$request->set_param( 'force', 'true' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['deleted'] );
		$this->assertNotEmpty( $data['previous'] );
	}

	/**
	 * @covers WP_REST_Templates_Controller::delete_item
	 */
	public function test_delete_item_fail() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'DELETE', '/wp/v2/templates/justrandom//template' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_template_not_found', $response, 404 );
	}

	public function test_prepare_item() {
		// TODO: Implement test_prepare_item() method.
	}

	public function test_prepare_item_limit_fields() {
		wp_set_current_user( self::$admin_id );

		$endpoint = new WP_REST_Templates_Controller( 'wp_template' );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/templates/default//my_template' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( '_fields', 'id,slug' );
		$obj      = get_block_template( 'default//my_template', 'wp_template' );
		$response = $endpoint->prepare_item_for_response( $obj, $request );
		$this->assertSame(
			array(
				'id',
				'slug',
			),
			array_keys( $response->get_data() )
		);
	}

	/**
	 * @ticket 54422
	 * @covers WP_REST_Templates_Controller::get_item_schema
	 */
	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wp/v2/templates' );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertCount( 14, $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'theme', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'source', $properties );
		$this->assertArrayHasKey( 'origin', $properties );
		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'wp_id', $properties );
		$this->assertArrayHasKey( 'has_theme_file', $properties );
		$this->assertArrayHasKey( 'is_custom', $properties );
		$this->assertArrayHasKey( 'author', $properties );
	}

	protected function find_and_normalize_template_by_id( $templates, $id ) {
		foreach ( $templates as $template ) {
			if ( $template['id'] === $id ) {
				unset( $template['content'] );
				unset( $template['_links'] );
				return $template;
			}
		}

		return null;
	}

}
