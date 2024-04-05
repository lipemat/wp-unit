<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  April 2024
 *
 */
class TearDownTest extends \WP_UnitTestCase {
	public function tear_down() {
		// Did not call tear_down().
	}


	public static function set_up_before_class() {
		parent::set_up_before_class();
		set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
			throw new \ErrorException( $errstr, $errno, 0, $errfile, $errline );
		} );
	}

	public static function tear_down_after_class() {
		try {
			parent::tear_down_after_class();
		} catch ( \ErrorException $e ) {
			self::assertSame( 'Test case did not tear down properly. Did you forget to call the `parent::tear_down` or `parent::tearDown` method?', $e->getMessage() );
		} finally {
			restore_error_handler();
			self::assertInstanceOf( \ErrorException::class, $e );
		}
	}


	public function test_missing_tear_down_call(): void {
		$this->expectException( \ErrorException::class );
		$this->expectExceptionMessage( 'Test case did not tear down properly. Did you forget to call the `parent::tear_down` or `parent::tearDown` method?' );

		parent::tear_down_after_class();
	}
}
