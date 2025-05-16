<?php
declare( strict_types=1 );

/**
 * A factory for making WordPress data with a cross-object type API.
 *
 * Tests should use this factory to generate test fixtures.
 */
class WP_UnitTest_Factory {

	/**
	 * Generates post fixtures for use in tests.
	 *
	 * @var WP_UnitTest_Factory_For_Post
	 */
	public WP_UnitTest_Factory_For_Post $post;

	/**
	 * Generates attachment fixtures for use in tests.
	 *
	 * @var WP_UnitTest_Factory_For_Attachment
	 */
	public WP_UnitTest_Factory_For_Attachment $attachment;

	/**
	 * Generates comment fixtures for use in tests.
	 *
	 * @var WP_UnitTest_Factory_For_Comment
	 */
	public WP_UnitTest_Factory_For_Comment $comment;

	/**
	 * Generates user fixtures for use in tests.
	 *
	 * @var WP_UnitTest_Factory_For_User
	 */
	public WP_UnitTest_Factory_For_User $user;

	/**
	 * Generates taxonomy term fixtures for use in tests.
	 *
	 * @var WP_UnitTest_Factory_For_Term
	 */
	public WP_UnitTest_Factory_For_Term $term;

	/**
	 * Generates category fixtures for use in tests.
	 *
	 * @var WP_UnitTest_Factory_For_Term
	 */
	public WP_UnitTest_Factory_For_Term $category;

	/**
	 * Generates tag fixtures for use in tests.
	 *
	 * @var WP_UnitTest_Factory_For_Term
	 */
	public WP_UnitTest_Factory_For_Term $tag;

	/**
	 * Generates blog (site) fixtures for use in Multisite tests.
	 *
	 * @var WP_UnitTest_Factory_For_Blog
	 */
	public WP_UnitTest_Factory_For_Blog $blog;

	/**
	 * Generates network fixtures for use in Multisite tests.
	 *
	 * @var WP_UnitTest_Factory_For_Network
	 */
	public WP_UnitTest_Factory_For_Network $network;

	public function __construct() {
		$this->post       = new WP_UnitTest_Factory_For_Post( $this );
		$this->attachment = new WP_UnitTest_Factory_For_Attachment( $this );
		$this->comment    = new WP_UnitTest_Factory_For_Comment( $this );
		$this->user       = new WP_UnitTest_Factory_For_User( $this );
		$this->term       = new WP_UnitTest_Factory_For_Term( $this );
		$this->category   = new WP_UnitTest_Factory_For_Term( $this, 'category' );
		$this->tag        = new WP_UnitTest_Factory_For_Term( $this, 'post_tag' );
		if ( is_multisite() ) {
			$this->blog    = new WP_UnitTest_Factory_For_Blog( $this );
			$this->network = new WP_UnitTest_Factory_For_Network( $this );
		}
	}
}
