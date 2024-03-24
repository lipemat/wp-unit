<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Framework;

/**
 * @author Mat Lipe
 * @since  March 2024
 *
 * @notice Must be run first to be valid.
 *
 */
class BootstrapTest extends \WP_UnitTestCase {
	public function test_switched_to_blog(): void {
		$this->assertFalse( ms_is_switched() );
	}
}
