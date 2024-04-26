<?php
/** @noinspection PhpRedundantCatchClauseInspection */
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  April 2024
 *
 */
class WP_Die_UsageTest extends \WP_UnitTestCase {

	public function tear_down() {
		parent::tear_down();

		// Verify our filter was removed for backwards compatibility.
		$this->assertFalse( has_filter( 'wp_die_handler', [ $this->wp_die_usage, 'get_handler' ] ) );
	}


	public function test_add_expected(): void {
		$_GET = [
			'preview_id'    => 1,
			'preview_nonce' => 'not valid',
		];
		$this->expectWpDie( 'Sorry, you are not allowed to preview drafts.' );
		_show_post_preview();

		$this->expectWpDie( 'Death', 500 );
		wp_die( 'Death', 500 );

	}


	public function test_backwards_compatibility(): void {
		try {
			wp_die( 'The link you followed has expired.');
		} catch ( \WPDieException $e ) {
			$caught = true;
			$this->assertEquals( 'The link you followed has expired.', $e->getMessage() );
		} finally {
			$this->assertTrue( isset( $caught ) && $caught );
		}
	}
}
