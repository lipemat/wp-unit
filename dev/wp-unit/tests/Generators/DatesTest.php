<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

/**
 * @author Mat Lipe
 * @since  May 2025
 *
 */
class DatesTest extends \WP_UnitTestCase {

	public function test_get_template_string(): void {
		$dates = new Dates();

		$date = $dates->get_template_string();
		$expected = \date_create( '-' . Dates::DAYS[0] . ' days', wp_timezone() );
		$this->assertSame( $expected->format( 'Y-m-d H:i:' ) . '00', $date );

		$date = $dates->get_template_string();
		$expected = \date_create( '-' . Dates::DAYS[1] . ' days', wp_timezone() );
		$this->assertSame( $expected->format( 'Y-m-d H:i:' ) . '00', $date );
	}


	public function test_use_with_factory(): void {
		$posts = self::factory()->post->create_many( 25, [], [
			'post_date' => new Dates(),
		] );
		$expected = [
			\date_create( '-12 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-3 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-17 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-17.1 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-8 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-20 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-20.5 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-1 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-15 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-15.9 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-6 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-14 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-10 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-2 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-19 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-5 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-21 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-7 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-18 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-13 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-4 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-16 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-9 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-11 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-12 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-3 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-17 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-17.1 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
		];
		foreach ( $posts as $index => $post ) {
			$this->assertSame( $expected[ $index ], get_the_date( 'Y-m-d H:i:s', $post ) );
		}
	}
}
