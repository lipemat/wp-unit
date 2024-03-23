<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  March 2024
 *
 */
class Hook_StateTest extends \WP_UnitTestCase {

	public function test_restore_wp_filter(): void {
		$hook_state = Hook_State::factory();
		$original = $hook_state->get_legacy_hooks();
		$hook_state->restore_wp_filter();
		foreach ( $GLOBALS['wp_filter'] as $hook_name => $hook_object ) {
			$this->assertNotSame( $original['wp_filter'][ $hook_name ], $hook_object );
		}
	}
}
