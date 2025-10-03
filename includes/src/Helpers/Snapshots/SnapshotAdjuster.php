<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers\Snapshots;

interface SnapshotAdjuster {
	/**
	 * Run value through the property matchers to return the value
	 * with matching keys or properties replaced.
	 *
	 * @return array|object
	 */
	public function get_adjusted_snapshot();
}
