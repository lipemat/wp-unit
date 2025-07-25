<?php
declare( strict_types=1 );

/**
 * Unit test factory for users.
 *
 * @phpstan-import-type GENERATORS from WP_UnitTest_Factory_For_Thing
 */
class WP_UnitTest_Factory_For_User extends WP_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'user_login' => new WP_UnitTest_Generator_Sequence( 'User %s' ),
			'user_pass'  => 'password',
			'user_email' => new WP_UnitTest_Generator_Sequence( 'user_%s@example.org' ),
		);
	}

	/**
	 * Inserts an user.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param array $args The user data to insert.
	 *
	 * @return int|WP_Error The user ID on success, WP_Error object on failure.
	 */
	public function create_object( array $args ) {
		return wp_insert_user( $args );
	}

	/**
	 * Updates the user data.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param int   $object_id ID of the user to update.
	 * @param array $fields    The user data to update.
	 *
	 * @return int|WP_Error The user ID on success, WP_Error object on failure.
	 */
	public function update_object( int $object_id, array $fields ) {
		$fields['ID'] = $object_id;
		return wp_update_user( $fields );
	}

	/**
	 * Retrieves the user for a given ID.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param int $object_id ID of the user ID to retrieve.
	 *
	 * @return WP_User The user object.
	 */
	public function get_object_by_id( int $object_id ): WP_User {
		return new WP_User( $object_id );
	}


	/**
	 * Creates a user and retrieves the user object.
	 *
	 * @since 4.3.0
	 *
	 * @phpstan-param GENERATORS|null $generation_definitions
	 *
	 * @param array                   $args                   Array with elements for the user.
	 * @param array|null              $generation_definitions Optional generation definitions.
	 *
	 * @return WP_User The user object.
	 */
	public function create_and_get( array $args = [], ?array $generation_definitions = null ): WP_User {
		return parent::create_and_get( $args, $generation_definitions );
	}

}
