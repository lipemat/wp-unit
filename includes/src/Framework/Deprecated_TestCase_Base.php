<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Framework;

use Lipe\WP_Unit\Helpers\Deprecated_Usage;
use Lipe\WP_Unit\Helpers\Doing_It_Wrong;
use Lipe\WP_Unit\Utils\Annotations;

/**
 * Deprecated methods which should not be used and
 * will be removed in the future.
 *
 * @since 3.7.0
 *
 * @property Deprecated_Usage $deprecated_usage
 * @property Doing_It_Wrong   $doing_it_wrong
 */
trait Deprecated_TestCase_Base {
	public $caught_deprecated = [];

	public $expected_deprecated = [];

	public $expected_doing_it_wrong = [];

	public $caught_doing_it_wrong = [];

	protected static $hooks_saved = [];

	/**
	 * Fixture factory.
	 *
	 * @deprecated 6.1.0 Use the WP_UnitTestCase_Base::factory() method instead.
	 *
	 * @var \WP_UnitTest_Factory
	 */
	protected $factory;

	/**
	 * @deprecated
	 */
	public function deprecated_function_run( $function_name, $replacement, $version, $message = '' ): void {
		if ( ! isset( $this->caught_deprecated[ $function_name ] ) ) {
			switch ( current_action() ) {
				case 'deprecated_function_run':
					$this->deprecated_usage->catch_function( $function_name, $replacement, $version );
					break;
				case 'deprecated_argument_run':
					$this->deprecated_usage->catch_argument( $function_name, $replacement, $version );
					break;
				case 'deprecated_class_run':
					$this->deprecated_usage->catch_class( $function_name, $replacement, $version );
					break;
				case 'deprecated_file_included':
					$this->deprecated_usage->catch_file( $function_name, $replacement, $version, $message );
					break;
				case 'deprecated_hook_run':
					$this->deprecated_usage->catch_hook( $function_name, $replacement, $version, $message );
					break;
			}
		}
	}


	/**
	 * Retrieves the name of the class the static method is called in.
	 *
	 * @deprecated 5.3.0 Use the PHP native get_called_class() function instead.
	 *
	 * @return string The class name.
	 */
	public static function get_called_class() {
		return get_called_class();
	}


	/**
	 * Allows tests to be skipped on single or multisite installs by using @group annotations.
	 *
	 * This is a custom extension of the PHPUnit requirements handling.
	 *
	 * @since      3.5.0
	 * @deprecated 5.9.0 This method has not been functional since PHPUnit 7.0.
	 */
	protected function checkRequirements() {
		// For PHPUnit 5/6, as we're overloading a public PHPUnit native method in those versions.
		if ( is_callable( 'PHPUnit\Framework\TestCase', 'checkRequirements' ) ) {
			parent::checkRequirements();
		}
	}

	/**
	 * @deprecated
	 */
	public function doing_it_wrong_run( $function_name, $message, $version ) {
		if ( ! isset( $this->caught_doing_it_wrong[ $function_name ] ) ) {
			$this->doing_it_wrong->catch( $function_name, $message, $version );

			$this->caught_doing_it_wrong[ $function_name ] = $this->doing_it_wrong->get_expected( $function_name );
		}
	}


	/**
	 * Formerly `expectDeprecated`
	 *
	 * @deprecated
	 */
	public function _fill_expected_deprecated(): void {
		$annotations = Annotations::instance()->get_annotations( $this );

		foreach ( [ 'class', 'method' ] as $depth ) {
			if ( ! empty( $annotations[ $depth ]['expectedDeprecated'] ) ) {
				$this->expected_deprecated = array_merge(
					$this->expected_deprecated,
					$annotations[ $depth ]['expectedDeprecated']
				);
			}

			if ( ! empty( $annotations[ $depth ]['expectedIncorrectUsage'] ) ) {
				$this->expected_doing_it_wrong = array_merge(
					$this->expected_doing_it_wrong,
					$annotations[ $depth ]['expectedIncorrectUsage']
				);
			}
		}
	}


	/**
	 * Redundant PHPUnit 6+ compatibility shim. DO NOT USE!
	 *
	 * This method is only left in place for backward compatibility reasons.
	 *
	 * @since      4.8.0
	 * @deprecated 5.9.0 Use the PHPUnit native expectException*() methods directly.
	 *
	 * @param mixed      $exception
	 * @param string     $message
	 * @param int|string $code
	 */
	public function setExpectedException( $exception, $message = '', $code = null ) {
		$this->expectException( $exception );

		if ( '' !== $message ) {
			$this->expectExceptionMessage( $message );
		}

		if ( null !== $code ) {
			$this->expectExceptionCode( $code );
		}
	}


	/**
	 * @deprecated
	 */
	public function expectedDeprecated() {
	}


	/**
	 * @deprecated
	 */
	public function setExpectedDeprecated( $deprecated ) {
		$this->expectDeprecated( $deprecated );
		$this->expected_deprecated[] = $deprecated;
	}


	/**
	 * @deprecated
	 */
	public function setExpectedIncorrectUsage( $doing_it_wrong ) {
		$this->expectDoingItWrong( $doing_it_wrong );
		$this->expected_doing_it_wrong[] = $doing_it_wrong;
	}


	/**
	 * @deprecated
	 */
	public function get_wp_die_handler( $handler ) {
		return [ $this->wp_die_usage, 'handler' ];
	}


	/**
	 * @deprecated
	 */
	public function wp_die_handler( $message, $title, $args ) {
		$this->wp_die_usage->handler( $message, $title, $args );
	}
}
