<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Utils;

use Lipe\WP_Unit\Exceptions\TestHelperException;
use Lipe\WP_Unit\Traits\Singleton;

/**
 * Helpers for working with classes.
 *
 * @author Mat Lipe
 * @since  4.8.0
 *
 */
class PrivateAccess {
	use Singleton;

	/**
	 * Get the value of a private constant or property from an object.
	 *
	 * @param class-string|object $object   An instantiated object or class name that we will run the method on.
	 * @param string              $property Property name or constant name to get.
	 *
	 * @throws TestHelperException
	 *
	 * @return mixed
	 */
	public function get_private_property( $object, string $property ) {
		try {
			$reflection = new \ReflectionClass( \is_string( $object ) ? $object : \get_class( $object ) );
		} catch ( \ReflectionException $e ) {
			return new TestHelperException( 'Could not reflect class: ' . $e->getMessage(), E_USER_ERROR );
		}
		if ( $reflection->hasProperty( $property ) ) {
			$reflection_property = $reflection->getProperty( $property );
			if ( PHP_VERSION_ID < 80200 ) {
				$reflection_property->setAccessible( true );
			}
			if ( $reflection_property->isStatic() ) {
				return $reflection_property->getValue();
			}
			if ( \is_string( $object ) ) {
				throw new TestHelperException( 'Getting a non-static value from a non-instantiated object is useless.', E_USER_ERROR );
			}
			return $reflection_property->getValue( $object );
		}
		if ( $reflection->hasConstant( $property ) ) {
			return $reflection->getConstant( $property );
		}
		throw new TestHelperException( 'Could not find property or constant: `' . $property . '`.', E_USER_ERROR );
	}


	/**
	 * Set the value of a private property on an object.
	 *
	 * @param class-string|object $object   An instantiated object to set property on.
	 * @param string              $property Property name to set.
	 * @param mixed               $value    Value to set.
	 *
	 * @throws TestHelperException
	 *
	 * @return void
	 */
	public function set_private_property( $object, string $property, $value ): void {
		try {
			$reflection = new \ReflectionClass( \is_string( $object ) ? $object : \get_class( $object ) );
		} catch ( \ReflectionException $e ) {
			throw new TestHelperException( 'Could not reflect class: ' . $e->getMessage(), E_USER_ERROR );
		}
		try {
			$reflection_property = $reflection->getProperty( $property );
			if ( PHP_VERSION_ID < 80200 ) {
				$reflection_property->setAccessible( true );
			}
		} catch ( \ReflectionException $e ) {
			throw new TestHelperException( 'Could not reflect property: ' . $e->getMessage(), E_USER_ERROR );
		}
		if ( $reflection_property->isStatic() ) {
			$reflection_property->setValue( null, $value );
		} else {
			if ( \is_string( $object ) ) {
				throw new TestHelperException( 'Setting a non-static value on a non-instantiated object is useless.', E_USER_ERROR );
			}
			$reflection_property->setValue( $object, $value );
		}
	}


	/**
	 * Call a protected / private method of a class.
	 *
	 * @param class-string|object $object      An instantiated object or class name that we will run the method on.
	 * @param string              $method_name Method name to call.
	 * @param array               $parameters  Array of parameters to pass into method.
	 *
	 * @throws TestHelperException
	 *
	 * @return mixed Method return.
	 */
	public function call_private_method( $object, string $method_name, array $parameters = [] ) {
		try {
			$reflection = new \ReflectionClass( \is_string( $object ) ? $object : \get_class( $object ) );
		} catch ( \ReflectionException $e ) {
			throw new TestHelperException( 'Could not reflect class: ' . $e->getMessage(), E_USER_ERROR );
		}
		try {
			if ( \is_string( $object ) ) {
				$object = $reflection->newInstanceWithoutConstructor();
			}
			$method = $reflection->getMethod( $method_name );
			if ( PHP_VERSION_ID < 80200 ) {
				$method->setAccessible( true );
			}
			return $method->invokeArgs( $object, $parameters );
		} catch ( \ReflectionException $e ) {
			throw new TestHelperException( 'Could not reflect method: ' . $e->getMessage(), E_USER_ERROR );
		}
	}
}
