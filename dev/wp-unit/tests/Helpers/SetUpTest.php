<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  April 2024
 *
 */
class SetUpTest extends \WP_UnitTestCase {
	public function set_up() {
		// Did not call set_up().
	}


	public function tear_down() {
		try {
			parent::tear_down();
		} catch ( \LogicException $e ) {
			$this->assertSame( 'Test case did not set up properly. Did you forget to call the `parent::set_up` or `parent::setUp` method?', $e->getMessage() );
		} finally {
			$this->assertInstanceOf( \LogicException::class, $e );
		}

		set_private_property( Setup_Teardown_State::class, 'setup', true );
	}


	public function test_missing_setup_call(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'Test case did not set up properly. Did you forget to call the `parent::set_up` or `parent::setUp` method?' );

		parent::tear_down();
	}
}
