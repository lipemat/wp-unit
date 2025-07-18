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
class Alphabetical implements Template_String {
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


	public function get_template_string(): string {
		$this->counter %= \count( static::TITLES );
		$title = static::TITLES[ $this->counter ];
		$this->counter ++;

		return $title;
	}
}
