<?php

/**
 * Unit test factory for posts.
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
	public function get_object_by_id( int $object_id ) {
		return get_post( $object_id );
	}
}
