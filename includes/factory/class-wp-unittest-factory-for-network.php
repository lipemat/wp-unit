<?php
declare( strict_types=1 );

/**
 * Unit test factory for networks.
 *
 * @phpstan-import-type GENERATORS from WP_UnitTest_Factory_For_Thing
 */
class WP_UnitTest_Factory_For_Network extends WP_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'domain'            => WP_TESTS_DOMAIN,
			'title'             => new WP_UnitTest_Generator_Sequence( 'Network %s' ),
			'path'              => new WP_UnitTest_Generator_Sequence( '/testpath%s/' ),
			'network_id'        => new WP_UnitTest_Generator_Sequence( '%s', 2 ),
			'subdomain_install' => false,
		);
	}

	/**
	 * Creates a network object.
	 *
	 * @since 3.9.0
	 * @since 6.2.0 Returns a WP_Error object on failure.
	 *
	 * @param array $args Arguments for the network object.
	 *
	 * @return int|WP_Error The network ID on success, WP_Error object on failure.
	 */
	public function create_object( array $args ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( isset( $args['user'] ) ) {
			$email = get_userdata( $args['user'] )->user_email;
		} else {
			$email = WP_TESTS_EMAIL;
		}

		$result = populate_network(
			$args['network_id'],
			$args['domain'],
			$email,
			$args['title'],
			$args['path'],
			$args['subdomain_install']
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return (int) $args['network_id'];
	}

	/**
	 * Updates a network object. Not implemented.
	 *
	 * @since 3.9.0
	 *
	 * @param int   $object_id ID of the network to update.
	 * @param array $fields    The fields to update.
	 */
	public function update_object( int $object_id, array $fields ): WP_Error {
		return new WP_Error( 'not_implemented', 'Network updates are not implemented.' );
	}

	/**
	 * Retrieves a network by a given ID.
	 *
	 * @since 3.9.0
	 *
	 * @param int $object_id ID of the network to retrieve.
	 *
	 * @return WP_Network|null The network object on success, null on failure.
	 */
	public function get_object_by_id( int $object_id ): ?WP_Network {
		return get_network( $object_id );
	}


	/**
	 * Creates a network and retrieves it.
	 *
	 * @since 4.3.0
	 *
	 * @phpstan-param GENERATORS|null $generation_definitions
	 *
	 * @param array                   $args                   Array with elements for the network.
	 * @param array|null              $generation_definitions Optional generation definitions.
	 *
	 * @return WP_Network The network object.
	 */
	public function create_and_get( array $args = [], ?array $generation_definitions = null ): WP_Network {
		return parent::create_and_get( $args, $generation_definitions );
	}
}
