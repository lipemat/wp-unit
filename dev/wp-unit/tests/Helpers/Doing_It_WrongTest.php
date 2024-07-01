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

	public function test_validate_messages(): void {
		$this->expectDoingItWrong( 'wp_add_inline_script', 'Do not pass <code>&lt;script&gt;</code> tags to <code>wp_add_inline_script()</code>. (This message was added in version 4.5.0.)' );
		wp_add_inline_script( 'noop', '<script></script>' );
	}


	public function test_multiple_expectations(): void {
		$this->expectDoingItWrong( 'wp_add_inline_script' );
		$this->expectDoingItWrong( 'wp_add_inline_script' );
		wp_add_inline_script( 'noop', '<script></script>' );
		wp_add_inline_script( 'noopx', '<script></script>' );
	}


	public function test_multiple_validate_messages(): void {
		$this->expectDoingItWrong( 'wp_add_inline_script', 'Do not pass <code>&lt;script&gt;</code> tags to <code>wp_add_inline_script()</code>. (This message was added in version 4.5.0.)' );
		$this->expectDoingItWrong( 'wp_add_inline_script', 'Do not pass <code>&lt;script&gt;</code> tags to <code>wp_add_inline_script()</code>. (This message was added in version 4.5.0.)' );
		wp_add_inline_script( 'noop', '<script></script>' );
		wp_add_inline_script( 'noopx', '<script></script>' );

		$this->expectDoingItWrong( 'register_post_type', 'Post type names must be between 1 and 20 characters in length. (This message was added in version 4.2.0.)'
		);
		register_post_type( 'super-long-post-type-name' );
	}
}
