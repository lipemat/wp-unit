<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  March 2024
 *
 */
class Global_HooksTest extends \WP_UnitTestCase {
	public const NAME = 'lipe/wp-unit/helpers/global-hookstest';


	public function test_make_changes(): void {
		$this->assertFalse( has_action( self::NAME, '__return_true' ) );
		add_action( self::NAME, '__return_true' );
		$this->assertSame( 10, has_action( self::NAME, '__return_true' ) );

		$this->assertFalse( has_filter( self::NAME . '-filter', '__return_true' ) );
		add_filter( self::NAME . '-filter', '__return_true', 20 );
		$this->assertSame( 20, has_filter( self::NAME . '-filter', '__return_true' ) );

		$this->assertEmpty( $GLOBALS['current_filter'] ?? null );
		$GLOBALS['wp_current_filter'] = [ 0, self::NAME ];
		$this->assertSame( self::NAME, current_filter() );

		$this->assertArrayNotHasKey( self::NAME, get_registered_meta_keys( 'post', 'page' ) );
		register_meta( 'post', self::NAME, [ 'object_subtype' => 'page' ] );
		$this->assertNotEmpty( get_registered_meta_keys( 'post', 'page' )[ self::NAME ] );

		$this->assertArrayNotHasKey( self::NAME, get_registered_settings() );
		register_setting( 'general', self::NAME, [ true ] );
		$this->assertNotEmpty( get_registered_settings()[ self::NAME ] );

		add_meta_box( 'test', 'test', '__return_true', 'post' );
		$this->assertNotEmpty( $GLOBALS['wp_meta_boxes']['post']['advanced']['default']['test'] );

		$this->assertFalse( wp_script_is( __FILE__ ) );
		wp_enqueue_script( __FILE__, 'test.js' );
		$this->assertTrue( wp_script_is( __FILE__ ) );

		$this->assertFalse( wp_style_is( __FILE__, 'enqueued' ) );
		wp_enqueue_style( __FILE__, 'test.css' );
		$this->assertTrue( wp_style_is( __FILE__, 'enqueued' ) );

		register_block_type( 'wp-unit/test', [
			'editor_script' => __FILE__,
		] );
		$this->assertSame( __FILE__, \WP_Block_Type_Registry::get_instance()->get_registered( 'wp-unit/test' )->editor_script_handles[0] );
	}


	/**
	 * @depends test_make_changes
	 */
	public function test_settings_reset(): void {
		$this->assertFalse( has_action( self::NAME, '__return_true' ) );
		$this->assertFalse( has_filter( self::NAME . '-filter', '__return_true' ) );
		$this->assertEmpty( $GLOBALS['current_filter'] ?? null );
		$this->assertArrayNotHasKey( self::NAME, get_registered_meta_keys( 'post', 'page' ) );
		$this->assertArrayNotHasKey( self::NAME, get_registered_settings() );
		$this->assertEmpty( $GLOBALS['wp_meta_boxes']['post']['advanced']['default']['test'] ?? null );

		$this->assertFalse( wp_script_is( __FILE__ ) );
		$this->assertFalse( wp_style_is( __FILE__, 'enqueued' ) );
	}


	/**
	 * @depends test_make_changes
	 */
	public function test_reset_block_type_registry(): void {
		$this->assertNull( \WP_Block_Type_Registry::get_instance()->get_registered( 'wp-unit/test' ) );
	}
}
