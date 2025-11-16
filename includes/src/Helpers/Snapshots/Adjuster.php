<?php
/** @noinspection PhpExpressionResultUnusedInspection, PhpUnhandledExceptionInspection, PhpDocMissingThrowsInspection */
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers\Snapshots;

use Lipe\WP_Unit\Exceptions\TestHelperException;
use Lipe\WP_Unit\Utils\PrivateAccess;

/**
 * Property or array key callback matcher for snapshots.
 *
 * - May be used to adjust snapshot data before comparison.
 * - May also be used outside snapshots to adjust data before matching.
 *
 * @author Mat Lipe
 * @since  4.7.0
 *
 * @see AdjusterTest::test_matches_snapshot for snapshot examples.
 * @see AdjusterTest::test_matches_snapshot_with_callback for invoke examples.
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
	 * Invoke this class as a callback directly.
	 *
	 * - May be called using `\array_map( $adjuster, $data)`
	 * - Or as a method on an object like `$adjuster($data)`
	 *
	 * @param array|object $data
	 *
	 * @return array|object
	 */
	public function __invoke( $data ) {
		$this->data = $data;
		return $this->get_adjusted_snapshot();
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
				try {
					$value = PrivateAccess::in()->get_private_property( $data, $key );
				} catch ( TestHelperException $e ) {
					throw new \InvalidArgumentException( 'Property `' . $key . '` does not exist.' );
				}
				PrivateAccess::in()->set_private_property( $data, $key, $callback->replace_value( $value, $data ) );
			}
		}
		return $data;
	}


	/**
	 * Create an adjuster to be invoked later with data.
	 *
	 * @see Adjuster::__invoke()
	 *
	 * @return Adjuster
	 */
	public static function create(): Adjuster {
		return new self( [] );
	}


	/**
	 * A simple helper to create a new instance.
	 *
	 * @phpstan-param array|object            $data      Array or object to match.
	 * @phpstan-param array<string, Callback> $callbacks Array of callbacks matching the keys or properties.
	 *                                                   May also be set using `replace` or `callback`.
	 */
	public static function factory( $data, array $callbacks = [] ): Adjuster {
		return new self( $data, $callbacks );
	}
}
