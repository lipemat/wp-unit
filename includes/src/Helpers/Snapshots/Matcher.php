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
 * @phpstan-type CALLBACK \Closure( mixed, array|object=): mixed
 *
 */
class Matcher implements SnapshotMatcher {
	/**
	 * Array or object to match.
	 *
	 * @var array|object
	 */
	protected $data;

	/**
	 * Array of callbacks matching the keys or properties.
	 *
	 * @var array<string, CALLBACK>
	 */
	protected array $callbacks;


	/**
	 * @param array|object            $data      Array or object to match.
	 * @param array<string, CALLBACK> $callbacks Array of callbacks matching the keys or properties.
	 */
	public function __construct( $data, array $callbacks = [] ) {
		$this->data = $data;
		$this->callbacks = $callbacks;
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
				$data[ $key ] = $this->match( $data[ $key ], $callback );
			} elseif ( \is_object( $data ) ) {
				$value = $this->get_private_property( $data, $key );
				$this->set_private_property( $data, $key, $this->match( $value, $callback ) );
			}
		}
		return $data;
	}


	/**
	 * Run a callback on a value.
	 *
	 * - Callback should have an assertion call inside it.
	 * - Callback must return the same value type as the value passed in.
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
	public function match( $value, \Closure $callback ) {
		return $callback( $value, $this->data );
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
