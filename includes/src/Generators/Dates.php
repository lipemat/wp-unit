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
		'12 days',
		'3 days',
		'17 days',
		'17 days 1 hour',
		'8 days',
		'20 days',
		'20 days 12 hours',
		'1 day',
		'15 days',
		'15 days 21 hours',
		'6 days',
		'14 days',
		'10 days',
		'2 days',
		'19 days',
		'5 days',
		'21 days',
		'7 days',
		'18 days',
		'13 days',
		'4 days',
		'16 days',
		'9 days',
		'11 days',
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
		$date = new \DateTime( '-' . static::DAYS[ $this->counter ], $this->timezone );
		// Set seconds to 0 to avoid issues tests taking more than 1 second.
		$date->setTime( (int) $date->format( 'H' ), (int) $date->format( 'i' ) );

		++ $this->counter;
		return $date->format( 'Y-m-d H:i:s' );
	}
}
