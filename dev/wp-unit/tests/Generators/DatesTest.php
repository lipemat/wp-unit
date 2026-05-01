<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

use Lipe\WP_Unit\Utils\PrivateAccess;

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
			\date_create( '-17 days 2 hours', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-8 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-20 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-20 days 12 hours', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-1 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-15 days', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
			\date_create( '-15 days 22 hours', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
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
			\date_create( '-17 days 2 hours', wp_timezone() )->format( 'Y-m-d H:i:' ) . '00',
		];
		foreach ( $posts as $index => $post ) {
			$this->assertSame( $expected[ $index ], get_the_date( 'Y-m-d H:i:s', $post ) );
			$this->assertLessThan( \time(), get_the_date( 'U', $post ), "$index is greater than now" );
			$this->assertSame( 'publish', get_post_status( $post ), "$index is not published" );
		}
	}


	public function test_get_next_date_string(): void {
		$generator = new Dates( wp_timezone() );
		$this->assertSame( '12 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '3 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '17 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '17 days 2 hours', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '8 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '20 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '20 days 12 hours', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '1 day', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '15 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '15 days 22 hours', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '6 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '14 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '10 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '2 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '19 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '5 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '21 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '7 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '18 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '13 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '4 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '16 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '9 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '11 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '12 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
		$this->assertSame( '3 days', PrivateAccess::instance()->call_private_method( $generator, 'get_next_date_string' ) );
	}
}
