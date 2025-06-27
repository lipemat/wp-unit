<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

/**
 * A generator which uses a callback to generate a value
 * for an object field after the object has been created.
 *
 * Will call `wp_update_<object_type>()` on the object
 * thereby triggering any update hooks that may be registered.
 *
 */
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
