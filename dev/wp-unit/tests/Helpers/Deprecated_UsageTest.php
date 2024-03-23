<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  March 2024
 *
 */
class Deprecated_UsageTest extends \WP_UnitTestCase {
	/**
	 * @expectedDeprecated     user_can_create_post
	 */
	public function test_validate(): void {
		// These 2 from annotations.
		user_can_create_post( 1 );

		$this->expectDeprecated( 'get_user_details' );
		get_user_details( 1 );

		$this->expectDeprecated( 'locale.php' );
		require ABSPATH . WPINC . '/locale.php';

		$this->expectDeprecated( 'unique-hook' );
		add_filter( 'unique-hook', '__return_true' );
		apply_filters_deprecated( 'unique-hook', [ true ], '1.0.0' );

		$this->expectDeprecated( 'is_email' );
		is_email( 'me@you.com', 'deprecated' );

		$this->expectDeprecated( \WP_User_Search::class );
		new \WP_User_Search();
	}
}
