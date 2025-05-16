<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

/**
 * Generate alphabetical titles for posts or other objects.
 *
 * Provides titles out of alphabetical order for testing ordering.
 *
 * @author Mat Lipe
 * @since  4.3.0
 *
 * @example
 *  ```php
 *  self::factory()->post->create_many(25, [], [
 *      'post_title' => new Alphabetical(),
 *  ] );
 * ```
 */
class Alphabetical implements Callback {
	public const TITLES = [
		'Zebra',
		'Alpha',
		'Echo',
		'Bravo',
		'November',
		'Charlie',
		'Check',
		'Oscar',
		'Quebec',
		'Delta',
		'Hotel',
		'India',
		'Display',
		'Foxtrot',
		'Golf',
		'Sierra',
		'Juliett',
		'Kilo',
		'Lima',
		'Mike',
		'Romeo',
		'Tango',
	];

	private int $counter = 0;


	/**
	 * @inheritDoc
	 */
	public function call( int $object_id ): string {
		$this->counter %= \count( self::TITLES );
		$title = self::TITLES[ $this->counter ];
		$this->counter ++;

		return $title;
	}
}
