<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * Track the state of the setup and tear doan method to
 * insure the parent methods are called and state is reset.
 *
 *
 *
 * @author Mat Lipe
 * @since  3.8.0
 *
 */
class Setup_Teardown_State {
	/**
	 * @var bool
	 */
	protected static $setup_before = false;

	/**
	 * @var bool
	 */
	protected static $setup = false;

	/**
	 * @var bool
	 */
	protected static $tear_down = false;

	/**
	 * @var array<string, bool>
	 */
	protected static $tear_down_after_classes = [];


	public static function set_up_before_class( string $class ): void {
		try {
			if ( \count( self::$tear_down_after_classes ) > 0 ) {
				$classes = \implode( ', ', \array_keys( self::$tear_down_after_classes ) );
				throw new \LogicException( $classes . ' did not tear down after class? Did you forget to call the `parent::tearDownAfterClass` or `parent::tear_down_after_class` method?' );
			}
		} finally {
			self::reset();
			self::$setup_before = true;
			self::$tear_down_after_classes[ $class ] = true;
		}
	}


	public static function set_up(): void {
		self::$setup = true;

		if ( ! self::$setup_before ) {
			throw new \LogicException( 'Test case did not set up properly. Did you forget to call the `parent::set_up_before_class` or `parent::setUpBeforeClass` method?' );
		}
	}


	public static function tear_down(): void {
		self::$tear_down = true;

		if ( ! self::$setup ) {
			throw new \LogicException( 'Test case did not set up properly. Did you forget to call the `parent::set_up` or `parent::setUp` method?' );
		}
	}


	/**
	 * @throws \ErrorException
	 */
	public static function tear_down_after_class( string $class ): void {
		unset( self::$tear_down_after_classes[ $class ] );

		try {
			if ( ! self::$tear_down ) {
				throw new \ErrorException( 'Test case did not tear down properly. Did you forget to call the `parent::tear_down` or `parent::tearDown` method?', E_USER_ERROR );
			}

			if ( ! self::$setup_before ) {
				throw new \ErrorException( 'Test case did not set up before properly?. Did you forget to call the `parent::set_up_before_class` or `parent::setUpBeforeClass` method?', E_USER_ERROR );
			}

			if ( ! self::$setup ) {
				throw new \ErrorException( 'Test case did not set up properly. Did you forget to call the `parent::set_up` or `parent::setUp` method?', E_USER_ERROR );
			}
		} finally {
			self::reset();
		}
	}


	protected static function reset(): void {
		self::$setup_before = false;
		self::$setup = false;
		self::$tear_down = false;
	}
}
