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
		$expected = \date_create( '-' . Dates::DAYS[0], wp_timezone() );
		$this->assertSame( $expected->format( 'Y-m-d H:i:' ) . '00', $date );

		$date = $dates->get_template_string();
		$expected = \date_create( '-' . Dates::DAYS[1], wp_timezone() );
		$this->assertSame( $expected->format( 'Y-m-d H:i:' ) . '00', $date );
	}


	public function test_use_with_factory(): void {
		$posts = self::factory()->post->create_many( 25, [], [
			'post_date' => new Dates(),
		] );
		$expected = \array_map( function( string $day ) {
			return \date_create( '-' . $day, wp_timezone() )->format( 'Y-m-d H:i:' ) . '00';
		}, \array_merge( Dates::DAYS, \array_slice( Dates::DAYS, 0, 25 - \count( Dates::DAYS ) ) ) );

		foreach ( $posts as $index => $post ) {
			$this->assertSame( $expected[ $index ], get_the_date( 'Y-m-d H:i:s', $post ) );
			$this->assertLessThan( \time(), get_the_date( 'U', $post ), "$index is greater than now" );
			$this->assertSame( 'publish', get_post_status( $post ), "$index is not published" );
		}
	}
}
