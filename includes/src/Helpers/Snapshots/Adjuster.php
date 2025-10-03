<?php
/** @noinspection PhpExpressionResultUnusedInspection, PhpUnhandledExceptionInspection, PhpDocMissingThrowsInspection */
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers\Snapshots;

/**
 * Property or array key callback matcher for snapshots.
 *
 * @author Mat Lipe
 * @since  4.7.0
 *
 * @see MatcherTest::test_matches_snapshot for some examples.
 *
 * @phpstan-type REPLACER \Closure( mixed, array|object=): mixed
 */
class Adjuster implements SnapshotAdjuster {
	/**
	 * Array or object to match.
	 *
	 * @var array|object
	 */
	protected $data;

	/**
	 * Array of callbacks matching the keys or properties.
	 *
	 * @phpstan-var array<string, Callback>
	 */
	protected array $callbacks;


	/**
	 * @phpstan-param array|object            $data      Array or object to match.
	 * @phpstan-param array<string, Callback> $callbacks Array of callbacks matching the keys or properties.
	 *                                                   May also be set using `replace` or `callback`.
	 */
	public function __construct( $data, array $callbacks = [] ) {
		$this->data = $data;
		$this->callbacks = $callbacks;
	}


	/**
	 * Replace the value of a key or property with a callback.
	 *
	 * @param string   $key
	 * @param REPLACER $callback
	 *
	 * @return $this
	 */
	public function replace( string $key, \Closure $callback ): Adjuster {
		$this->callbacks[ $key ] = Callback::factory( $callback );
		return $this;
	}


	/**
	 * Add a custom callback to match a key or property.
	 *
	 * Only needed if the default callback is not enough.
	 *
	 * @see Adjuster::replace()
	 *
	 * @param string   $key
	 * @param Callback $callback
	 *
	 * @return $this
	 */
	public function callback( string $key, Callback $callback ): Adjuster {
		$this->callbacks[ $key ] = $callback;
		return $this;
	}


	/**
	 * Run value through the property matchers to return the value
	 * with matching keys or properties replaced.
	 *
	 * @return array|object
	 */
	public function get_adjusted_snapshot() {
		$data = $this->data;
		foreach ( $this->callbacks as $key => $callback ) {
			if ( \is_array( $data ) ) {
				if ( ! isset( $data[ $key ] ) ) {
					throw new \InvalidArgumentException( 'Key ' . $key . ' does not exist in the array.' );
				}
				$data[ $key ] = $callback->replace_value( $data[ $key ], $data );
			} elseif ( \is_object( $data ) ) {
				$value = $this->get_private_property( $data, $key );
				$this->set_private_property( $data, $key, $callback->replace_value( $value, $data ) );
			}
		}
		return $data;
	}


	/**
	 * Get the value of a private constant or property from an object.
	 *
	 * @param object $object   An instantiated object or class name that we will run a method on.
	 * @param string $property Property name or constant name to get.
	 *
	 * @return mixed
	 */
	protected function get_private_property( object $object, string $property ) {
		$reflection = new \ReflectionClass( \get_class( $object ) );
		if ( $reflection->hasProperty( $property ) ) {
			$reflection_property = $reflection->getProperty( $property );
			$reflection_property->setAccessible( true );
			return $reflection_property->getValue( $object );
		}
		throw new \InvalidArgumentException( "Property `{$property}` does not exist." );
	}


	/**
	 * Set the value of a private property on an object.
	 *
	 * @param object $object   An instantiated object to set property on.
	 * @param string $property Property name to set.
	 * @param mixed  $value    Value to set.
	 *
	 * @throws \ReflectionException
	 *
	 * @return void
	 */
	protected function set_private_property( object $object, string $property, $value ): void {
		$reflection = new \ReflectionClass( \get_class( $object ) );
		$reflection_property = $reflection->getProperty( $property );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( $object, $value );
	}
}
