<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Utils;

use Lipe\WP_Unit\Traits\Singleton;

/**
 * Helpers for working with classes.
 *
 * @author Mat Lipe
 * @since  4.8.0
 *
 */
class Classes {
	use Singleton;

	/**
	 * Get the value of a private constant or property from an object.
	 *
	 * @param object $object   An instantiated object or class name that we will run a method on.
	 * @param string $property Property name or constant name to get.
	 *
	 * @return mixed
	 */
	public function get_private_property( object $object, string $property ) {
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
	public function set_private_property( object $object, string $property, $value ): void {
		$reflection = new \ReflectionClass( \get_class( $object ) );
		$reflection_property = $reflection->getProperty( $property );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( $object, $value );
	}
}
