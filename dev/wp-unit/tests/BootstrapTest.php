<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit;

/**
 * Test the bootstrap.php file.
 *
 * @author Mat Lipe
 * @since  March 2024
 *
 * @notice Must be run first to be valid, so we added it to the main phpunit.xml.dist file
 *         to be run before all other tests.
 *
 */
class BootstrapTest extends \WP_UnitTestCase {
	protected function tearDown(): void {
		parent::tearDown();

		$this->assertFalse( $GLOBALS['bootstrap/testing'] );
		$this->assertSame( 0, did_action( 'wp-unit/set_up' ) );
	}


	public function test_setup_action(): void {
		$this->assertTrue( $GLOBALS['bootstrap/testing'] );
		$this->assertSame( 1, did_action( 'wp-unit/set_up' ) );
	}

	public function test_switched_to_blog(): void {
		$this->assertFalse( ms_is_switched() );
	}
}
