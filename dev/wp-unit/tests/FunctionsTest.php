<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit;

/**
 * Test the functions.php file.
 *
 * @author Mat Lipe
 * @since  January 2026
 *
 */
class FunctionsTest extends \WP_UnitTestCase {
	public function test_tests_add_option(): void {
		$this->assertSame( 'strapped-on', get_option( 'bootstrap/testing' ) );
		$this->assertSame( 'strapped-on', get_network_option( null, 'bootstrap/testing' ) );

		update_option( 'bootstrap/testing', 'strapped-off' );
		$this->assertSame( 'strapped-off', get_option( 'bootstrap/testing' ) );
		$this->assertSame( 'strapped-on', get_network_option( null, 'bootstrap/testing' ) );

		update_network_option( null, 'bootstrap/testing', 'strapped-off' );
		$this->assertSame( 'strapped-off', get_option( 'bootstrap/testing' ) );
		$this->assertSame( 'strapped-off', get_network_option( null, 'bootstrap/testing' ) );

		$this->assertSame( 'editor', get_option( 'default_role' ) );
		$this->assertSame( 'editor', get_network_option( null, 'default_role' ) );
	}
}
