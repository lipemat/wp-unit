<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;


/**
 * @author Mat Lipe
 * @since  April 2024
 *
 */
class SetUpBeforeClassTest extends \WP_UnitTestCase {
	public static function set_up_before_class() {
	}

	public static function tear_down_after_class() {
		set_private_property( Setup_Teardown_State::class, 'setup_before', true );
		parent::tear_down_after_class();
	}


	public function setUp(): void {
		try {
			parent::setUp();
		} catch ( \LogicException $e ) {
			$this->assertSame( 'Test case did not set up properly. Did you forget to call the `parent::set_up_before_class` or `parent::setUpBeforeClass` method?', $e->getMessage() );
		} finally {
			$this->assertInstanceOf( \LogicException::class, $e );
		}
	}

	public function test_missing_setup_call(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'Test case did not set up properly. Did you forget to call the `parent::set_up_before_class` or `parent::setUpBeforeClass` method?' );

		self::set_up();
	}
}
