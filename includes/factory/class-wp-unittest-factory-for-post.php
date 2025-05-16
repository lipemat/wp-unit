<?php
declare( strict_types=1 );

/**
 * Unit test factory for posts.
 *
 * @phpstan-import-type GENERATORS from WP_UnitTest_Factory_For_Thing
 *
 */
class WP_UnitTest_Factory_For_Post extends WP_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = [
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'Post title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Post content %s' ),
			'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Post excerpt %s' ),
			'post_type'    => 'post',
		];
	}


	/**
	 * Creates a post object.
	 *
	 * @since UT (3.7.0)
	 * @since 6.2.0 Returns a WP_Error object on failure.
	 *
	 * @param array $args Array with elements for the post.
	 *
	 * @return int|WP_Error The post ID on success, WP_Error object on failure.
	 */
	public function create_object( array $args ) {
		return wp_insert_post( $args, true );
	}


	/**
	 * Updates an existing post object.
	 *
	 * @since UT (3.7.0)
	 * @since 6.2.0 Returns a WP_Error object on failure.
	 *
	 * @param int   $object_id ID of the post to update.
	 * @param array $fields    Post data.
	 *
	 * @return int|WP_Error The post ID on success, WP_Error object on failure.
	 */
	public function update_object( int $object_id, array $fields ) {
		$fields['ID'] = $object_id;
		return wp_update_post( $fields, true );
	}


	/**
	 * Retrieves a post by a given ID.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param int $object_id ID of the post to retrieve.
	 *
	 * @return WP_Post|null WP_Post object on success, null on failure.
	 */
	public function get_object_by_id( int $object_id ): ?WP_Post {
		return get_post( $object_id );
	}


	/**
	 * Creates a post and retrieves it.
	 *
	 * @since 4.3.0
	 *
	 * @phpstan-param GENERATORS|null $generation_definitions
	 *
	 * @param array                   $args                   Array with elements for the post.
	 * @param array|null              $generation_definitions Optional generation definitions.
	 *
	 * @return WP_Post
	 */
	public function create_and_get( array $args = [], ?array $generation_definitions = null ): WP_Post {
		return parent::create_and_get( $args, $generation_definitions );
	}

}
