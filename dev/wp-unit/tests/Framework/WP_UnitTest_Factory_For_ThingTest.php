<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Framework;

/**
 * @author Mat Lipe
 * @since  May 2025
 *
 */
class WP_UnitTest_Factory_For_ThingTest extends \WP_UnitTestCase {
	public function test_custom_post_titles(): void {
		[ $content_1, $content_2, $content_3, $content_4 ] = self::factory()->post->create_many( 4, [], [
			'post_title' => $this->alpha_title(),
		] );

		$this->assertSame( 'Zebra', get_the_title( $content_1 ) );
		$this->assertSame( 'Alpha', get_the_title( $content_2 ) );
		$this->assertSame( 'Echo', get_the_title( $content_3 ) );
		$this->assertSame( 'Bravo', get_the_title( $content_4 ) );
	}


	private function alpha_title(): \WP_UnitTest_Factory_Callback_After_Create {
		return self::factory()->post->callback( function( $post_id ) {
			static $counter = - 1;
			++ $counter;
			$titles = [
				'Zebra',
				'Alpha',
				'Echo',
				'Bravo',
			];
			return $titles[ $counter ];
		} );
	}
}
