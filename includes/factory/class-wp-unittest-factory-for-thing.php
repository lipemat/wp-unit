<?php
declare( strict_types=1 );

use Lipe\WP_Unit\Generators\Callback;
use Lipe\WP_Unit\Generators\Template_String;

/**
 * Serves as a basis for all WordPress object-type factory classes.
 *
 * @noinspection PhpUndefinedClassInspection
 * @phpstan-type GENERATORS array<string, Template_String|Callback|scalar>
 *
 */
abstract class WP_UnitTest_Factory_For_Thing {

	/**
	 * @phpstan-var GENERATORS
	 */
	public array $default_generation_definitions;

	public \WP_UnitTest_Factory $factory;

	/**
	 * Creates a new factory, which will create objects of a specific Thing.
	 *
	 * @since UT (3.7.0)
	 *
	 * @phpstan-param GENERATORS   $default_generators
	 *
	 * @param \WP_UnitTest_Factory $factory            Global factory that can be used to create other objects
	 *                                              on the system.
	 * @param array                $default_generators Optional. The default values for the object properties.
	 */
	public function __construct( \WP_UnitTest_Factory $factory, array $default_generators = [] ) {
		$this->factory                        = $factory;
		$this->default_generation_definitions = $default_generators;
	}


	/**
	 * Creates an object and returns its ID.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param array $args The arguments.
	 *
	 * @return int|WP_Error The object ID on success, WP_Error object on failure.
	 */
	abstract public function create_object( array $args );

	/**
	 * Updates an existing object.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param int   $object_id The object ID.
	 * @param array $fields    The values to update.
	 *
	 * @return int|WP_Error The object ID on success, WP_Error object on failure.
	 */
	abstract public function update_object( int $object_id, array $fields );

	/**
	 * Creates an object and returns its ID.
	 *
	 * @since UT (3.7.0)
	 *
	 * @phpstan-param GENERATORS|null $generation_definitions
	 *
	 * @param array<string, mixed>    $args                   Optional. The arguments for the object to create.
	 * @param array|null              $generation_definitions Optional. Generators or values to use for the object properties.
	 *
	 * @return int|WP_Error The object ID on success, WP_Error object on failure.
	 */
	public function create( array $args = [], ?array $generation_definitions = null ) {
		$callbacks = [];
		$generated_args = $this->generate_args( $args, $generation_definitions, $callbacks );
		$object_id      = $this->create_object( $generated_args );

		if ( 0 === $object_id || is_wp_error( $object_id ) ) {
			return $object_id;
		}

		if ( \count( $callbacks ) > 0 ) {
			$updated_fields = $this->apply_callbacks( $callbacks, $object_id );
			$save_result    = $this->update_object( $object_id, $updated_fields );

			if ( 0 === $save_result || is_wp_error( $save_result ) ) {
				return $save_result;
			}
		}

		return $object_id;
	}

	/**
	 * Creates and returns an object.
	 *
	 * @since UT (3.7.0)
	 *
	 * @phpstan-param GENERATORS|null $generation_definitions
	 *
	 * @param array<string, mixed>    $args                   Optional. The arguments for the object to create.
	 * @param array|null              $generation_definitions Optional. Generators or values to use for the object properties.
	 *
	 * @return mixed
	 */
	public function create_and_get( array $args = [], ?array $generation_definitions = null ) {
		$object_id = $this->create( $args, $generation_definitions );

		if ( is_wp_error( $object_id ) ) {
			return $object_id;
		}

		return $this->get_object_by_id( $object_id );
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return mixed The object. Can be anything.
	 */
	abstract public function get_object_by_id( int $object_id );

	/**
	 * Creates multiple objects.
	 *
	 * @since UT (3.7.0)
	 *
	 *
	 * @phpstan-param GENERATORS|null $generation_definitions
	 *
	 * @param int                     $count                  Number of objects to create.
	 * @param array<string, mixed>    $args                   Optional. The arguments for the object to create.
	 * @param array|null              $generation_definitions Optional. Generators or values to use for the object properties.
	 *
	 * @return int[]
	 */
	public function create_many( int $count, array $args = [], ?array $generation_definitions = null ): array {
		$results = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$results[] = $this->create( $args, $generation_definitions );
		}

		return $results;
	}

	/**
	 * Combines the given arguments with the generation_definitions (defaults) and applies
	 * possibly set callbacks on it.
	 *
	 * @since UT (3.7.0)
	 *
	 * @template T of GENERATORS
	 * @phpstan-param T  $args
	 *
	 * @param array      $args                   Optional. The arguments to combine with defaults.
	 * @param array|null $generation_definitions Optional. Generators or values to use for the object properties.
	 * @param array      $callbacks              Optional. Array with callbacks to apply on the fields.
	 *
	 *
	 * @phpstan-return T|\WP_Error
	 * @return array|\WP_Error
	 */
	public function generate_args( array $args = [], ?array $generation_definitions = null, array &$callbacks = [] ) {
		$callbacks = array();
		if ( \is_array( $generation_definitions ) ) {
			$generation_definitions = \array_merge( $this->default_generation_definitions, $generation_definitions );
		} else {
			$generation_definitions = $this->default_generation_definitions;
		}

		// Use the same incrementor for all fields belonging to this object.
		$gen = new WP_UnitTest_Generator_Sequence();
		// Add leading zeros to make sure MySQL sorting works as expected.
		$incr = zeroise( $gen->get_incr(), 7 );

		foreach ( array_keys( $generation_definitions ) as $field_name ) {
			if ( ! isset( $args[ $field_name ] ) ) {
				$generator = $generation_definitions[ $field_name ];
				if ( \is_scalar( $generator ) ) {
					$args[ $field_name ] = $generator;
				} elseif ( $generator instanceof Callback ) {
					$callbacks[ $field_name ] = $generator;
				} elseif ( $generator instanceof Template_String ) {
					$args[ $field_name ] = \sprintf( $generator->get_template_string(), $incr );
				} else {
					return new \WP_Error(
						'invalid_argument',
						'Factory default value must be a scalar or a generator object.'
					);
				}
			}
		}

		return $args;
	}


	/**
	 * Applies the callbacks on the created object.
	 *
	 * @since UT (3.7.0)
	 *
	 * @see WP_UnitTest_Factory_For_Thing::callback
	 *
	 * @template T of GENERATORS
	 * @phpstan-param T $callbacks
	 *
	 * @param array     $callbacks Array of object fields, and their corresponding callback objects.
	 * @param int       $object_id ID of the object to apply callbacks for.
	 *
	 * @phpstan-return T
	 * @return array The altered fields.
	 */
	public function apply_callbacks( array $callbacks, int $object_id ): array {
		return \array_map( function( $generator ) use ( $object_id ) {
			return $generator->call( $object_id );
		}, $callbacks );
	}

	/**
	 * Instantiates a callback object for the given function name.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param callable $callback The callback function.
	 *
	 * @return \WP_UnitTest_Factory_Callback_After_Create
	 */
	public function callback( callable $callback ): WP_UnitTest_Factory_Callback_After_Create {
		return new \WP_UnitTest_Factory_Callback_After_Create( $callback );
	}

	/**
	 * Adds slashes to the given value.
	 *
	 * @since UT (3.7.0)
	 *
	 * @param array|object|string|mixed $value The value to add slashes to.
	 *
	 * @return array|string The value with the possibly applied slashes.
	 */
	public function addslashes_deep( $value ) {
		if ( \is_array( $value ) ) {
			$value = \array_map( [ $this, 'addslashes_deep' ], $value );
		} elseif ( \is_object( $value ) ) {
			$vars = get_object_vars( $value );
			foreach ( $vars as $key => $data ) {
				// @phpstan-ignore-next-line -- Variable access on an object.
				$value->{$key} = $this->addslashes_deep( $data );
			}
		} elseif ( \is_string( $value ) ) {
			$value = addslashes( $value );
		}

		return $value;
	}
}
