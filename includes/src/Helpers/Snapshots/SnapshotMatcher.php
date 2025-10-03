<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers\Snapshots;

interface SnapshotMatcher {
	/**
	 * Run value through the property matchers to return the value
	 * with matching keys or properties replaced.
	 *
	 * @return array|object
	 */
	public function get_adjusted_snapshot();


	/**
	 * Run a callback on a value.
	 *
	 * - Callback should have an assertion call inside it.
	 * - Callback must return a descriptive string (e.g., 'Date<callback>')
	 *
	 * @template T
	 * @phpstan-param T                            $value
	 *
	 * @param mixed                                $value
	 * @param (\Closure( mixed, array|object=): T) $callback
	 *
	 * @phpstan-return T
	 * @return mixed
	 */
	public function match( $value, \Closure $callback );
}
