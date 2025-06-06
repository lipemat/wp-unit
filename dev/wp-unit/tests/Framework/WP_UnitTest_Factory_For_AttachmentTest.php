<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Framework;

/**
 * @author Mat Lipe
 * @since  May 2025
 *
 */
class WP_UnitTest_Factory_For_AttachmentTest extends \WP_UnitTestCase {
	public function test_create_object(): void {
		$attachment = self::factory()->attachment->create( [
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Test Image',
			'post_content'   => 'Test Content',
			'post_excerpt'   => 'Test Excerpt',
		] );

		$this->assertSame( 'image/jpg', get_post_mime_type( $attachment ) );
		$this->assertSame( 'Test Image', get_the_title( $attachment ) );
		$this->assertSame( 'Test Content', get_post_field( 'post_content', $attachment ) );
		$this->assertSame( 'Test Excerpt', get_post_field( 'post_excerpt', $attachment ) );
	}


	public function test_create_object_simiulated_image_sizes(): void {
		add_image_size( 'my-content-thumb', 548 );
		add_image_size( 'test-size-medium', 274, 205, true );
		$attachment = self::factory()->attachment->create( [
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Test JPG',
		] );

		$dir = wp_upload_dir();
		$this->assertMatchesRegularExpression( '#https://wp-unit\.loc/wp-content/uploads/\d{4}/\d{2}#', $dir['url'] );

		$this->assertSame( $dir['url'] . '/test-image-548x0.jpg', wp_get_attachment_image_url( $attachment, 'my-content-thumb' ) );;
		$this->assertSame( $dir['url'] . '/test-image-274x205.jpg', wp_get_attachment_image_url( $attachment, 'test-size-medium' ) );

		$this->assertSame( $dir['url'] . '/test-image.jpg', wp_get_attachment_image_url( $attachment, 'full' ) );
	}
}
