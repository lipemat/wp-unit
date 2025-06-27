<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

/**
 * @author Mat Lipe
 * @since  May 2025
 *
 */
class AlphabeticalTest extends \WP_UnitTestCase {

	public function test_get_template_string(): void {
		$alphabetical = new Alphabetical();

		$title = $alphabetical->get_template_string();
		$this->assertSame( Alphabetical::TITLES[0], $title );
		$title = $alphabetical->get_template_string();
		$this->assertSame( Alphabetical::TITLES[1], $title );
	}


	public function test_use_with_factory(): void {
		$posts = self::factory()->post->create_many( 25, [], [
			'post_title' => new Alphabetical(),
		] );
		$this->assertSame( 'Zebra', get_the_title( $posts[0] ) );
		$this->assertSame( 'Alpha', get_the_title( $posts[1] ) );
		$this->assertSame( 'Echo', get_the_title( $posts[2] ) );
		$this->assertSame( 'Bravo', get_the_title( $posts[3] ) );
		$this->assertSame( 'November', get_the_title( $posts[4] ) );
		$this->assertSame( 'Charlie', get_the_title( $posts[5] ) );
		$this->assertSame( 'Check', get_the_title( $posts[6] ) );
		$this->assertSame( 'Oscar', get_the_title( $posts[7] ) );
		$this->assertSame( 'Quebec', get_the_title( $posts[8] ) );
		$this->assertSame( 'Delta', get_the_title( $posts[9] ) );
		$this->assertSame( 'Hotel', get_the_title( $posts[10] ) );
		$this->assertSame( 'India', get_the_title( $posts[11] ) );
		$this->assertSame( 'Display', get_the_title( $posts[12] ) );
		$this->assertSame( 'Foxtrot', get_the_title( $posts[13] ) );
		$this->assertSame( 'Golf', get_the_title( $posts[14] ) );
		$this->assertSame( 'Sierra', get_the_title( $posts[15] ) );
		$this->assertSame( 'Juliett', get_the_title( $posts[16] ) );
		$this->assertSame( 'Kilo', get_the_title( $posts[17] ) );
		$this->assertSame( 'Lima', get_the_title( $posts[18] ) );
		$this->assertSame( 'Mike', get_the_title( $posts[19] ) );
		$this->assertSame( 'Romeo', get_the_title( $posts[20] ) );
		$this->assertSame( 'Tango', get_the_title( $posts[21] ) );
		$this->assertSame( 'Zebra', get_the_title( $posts[22] ) );
		$this->assertSame( 'Alpha', get_the_title( $posts[23] ) );
		$this->assertSame( 'Echo', get_the_title( $posts[24] ) );
	}
}
