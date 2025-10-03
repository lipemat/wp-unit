<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers\Snapshots;

/**
 * Interface for snapshot replacements.
 *
 * @author Mat Lipe
 * @since  4.7.0
 */
interface Replace {
	/**
	 * Run a callback on a value.
	 *
	 * - Callback should have an assertion call inside it.
	 * - Callback must return the same value type as the value passed in.
	 *
	 * @template T
	 * @phpstan-param T    $value
	 *
	 * @param mixed        $value
	 * @param array|object $data
	 *
	 * @phpstan-return T
	 * @return mixed
	 */
	public function replace_value( $value, $data );
}
