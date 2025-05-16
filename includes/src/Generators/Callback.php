<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

interface Callback {
	/**
	 * Calls the set callback on a given object.
	 *
	 * @param int $object_id ID of the object to apply the callback on.
	 *
	 * @return mixed Updated object field.
	 */
	public function call( int $object_id );
}
