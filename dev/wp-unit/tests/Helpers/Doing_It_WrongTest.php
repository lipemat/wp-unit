<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  March 2024
 *
 */
class Doing_It_WrongTest extends \WP_UnitTestCase {
	/**
	 * @expectedIncorrectUsage register_post_type
	 */
	public function test_validate(): void {
		// These 2 from annotations.
		register_post_type( 'super-long-post-type-name' );

		$this->expectDoingItWrong( 'wp_add_inline_script' );
		wp_add_inline_script( 'noop', '<script></script>' );
	}
}
