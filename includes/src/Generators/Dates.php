<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

use DateTimeZone;

/**
 * Generate dates for posts or other objects.
 *
 * Provides dates out of order for testing ordering.
 *
 * @author Mat Lipe
 * @since  4.3.0
 *
 * @example
 *   ```php
 *   self::factory()->post->create_many(25, [], [
 *      'post_date' => new Dates(),
 *   ] );
 *  ```
 *
 */
class Dates implements Template_String {
	public const DAYS = [
		12,
		3,
		17,
		17.1,
		8,
		20,
		20.5,
		1,
		15,
		15.9,
		6,
		14,
		10,
		2,
		19,
		5,
		21,
		7,
		18,
		13,
		4,
		16,
		9,
		11,
	];

	private DateTimeZone $timezone;

	private int $counter = 0;


	public function __construct( ?DateTimeZone $timezone = null ) {
		if ( null === $timezone ) {
			$timezone = wp_timezone();
		}

		$this->timezone = $timezone;
	}


	/**
	 * @throws \Exception - If the date cannot be created.
	 */
	public function get_template_string(): string {
		$this->counter %= \count( static::DAYS );
		$date = new \DateTime( '-' . static::DAYS[ $this->counter ] . ' days', $this->timezone );
		// Set seconds to 0 to avoid issues tests taking more than 1 second.
		$date->setTime( (int) $date->format( 'H' ), (int) $date->format( 'i' ) );

		++ $this->counter;
		return $date->format( 'Y-m-d H:i:s' );
	}
}
