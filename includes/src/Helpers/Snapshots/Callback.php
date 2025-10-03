<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers\Snapshots;

/**
 * Simple class for implementing a snapshot matcher `ReplaceMatch` interface.
 *
 * - Used internally by the Matcher class.
 * - May also be used locally in tests.
 *
 * @author Mat Lipe
 * @since  4.7.0
 *
 * @phpstan-import-type REPLACER from Adjuster
 */
class Callback implements Replace {
	/**
	 * @var REPLACER
	 */
	public \Closure $callback;


	/**
	 * Callback constructor.
	 *
	 * @param REPLACER $callback A callback function.
	 */
	final public function __construct( \Closure $callback ) {
		$this->callback = $callback;
	}


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
	public function replace_value( $value, $data ) {
		return \call_user_func( $this->callback, $value, $data );
	}


	/**
	 * A simple helper to create a new instance.
	 *
	 * @param \Closure $callback
	 *
	 * @return Callback
	 */
	public static function factory( \Closure $callback ): Callback {
		return new static( $callback );
	}
}
