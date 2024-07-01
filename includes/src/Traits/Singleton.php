<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Traits;

/**
 * @author Mat Lipe
 * @since  3.7.0
 *
 */
trait Singleton {
	final private function __construct() {
	}


	protected function hook(): void {
	}


	/**
	 * Instance of this class for use as singleton.
	 *
	 * @var static
	 */
	protected static $instance;

	/**
	 * @var bool
	 */
	protected static $initialized = false;


	/**
	 * Create the instance of the class
	 *
	 * @return void
	 */
	public static function init(): void {
		static::$instance = static::instance();
		static::$instance->hook();
		static::$initialized = true;
	}


	/**
	 * Call this method as many times as needed, and the
	 * class will only init() one time.
	 *
	 * @return void
	 */
	public static function init_once(): void {
		if ( ! static::$initialized ) {
			static::init();
		}
	}


	/**
	 * @return static
	 */
	public static function instance() {
		if ( ! is_a( static::$instance, __CLASS__ ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}
