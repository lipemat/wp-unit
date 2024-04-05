<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  April 2024
 *
 */
class TearDownAfterClassTest extends \WP_UnitTestCase {
	public static function tear_down_after_class() {
	}


	public function tear_down(): void {
		set_private_property( Setup_Teardown_State::class, 'setup', true );
		parent::tear_down();
	}


	public function test_missing_tear_down_after_class_call(): void {
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'Lipe\WP_Unit\Helpers\TearDownAfterClassTest did not tear down after class? Did you forget to call the `parent::tearDownAfterClass` or `parent::tear_down_after_class` method?' );

		try {
			self::tear_down_after_class();
			self::set_up_before_class();
		} finally {
			set_private_property( Setup_Teardown_State::class, 'tear_down_after_classes', [] );
		}
	}
}
