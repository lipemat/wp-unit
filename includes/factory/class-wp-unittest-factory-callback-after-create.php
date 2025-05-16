<?php
declare( strict_types=1 );

use Lipe\WP_Unit\Generators\Callback;

class WP_UnitTest_Factory_Callback_After_Create implements Callback {

	/**
	 * @var callable
	 */
	public $callback;

	/**
	 * Callback constructor.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param callable $callback A callback function.
	 */
	public function __construct( callable $callback ) {
		$this->callback = $callback;
	}

	/**
	 * Calls the set callback on a given object.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param int $object_id ID of the object to apply the callback on.
	 *
	 * @return mixed Updated object field.
	 */
	public function call( int $object_id ) {
		return \call_user_func( $this->callback, $object_id );
	}
}
