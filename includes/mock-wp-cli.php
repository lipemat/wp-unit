<?php

/**
 * Stub the WP_Cli class.
 *
 * Allow basic commands to be run within tests and results to be asserted.
 */

namespace {
	use WP_CLI\ExitException;

	class WP_CLI {

		/**
		 * @var WP_CLI_UnitTestCase
		 */
		protected static $case;


		public static function __provide_test_case( WP_CLI_UnitTestCase $case ): void {
			self::$case = $case;
		}


		/**
		 * Store messages in this class.
		 *
		 * @param string $message Message to output.
		 *
		 * @return void
		 */
		public static function success( string $message ): void {
			self::$case->success[] = "Success: {$message}";
		}


		/**
		 * Store messages in this class.
		 *
		 * This and log() do the same thing.
		 *
		 * @param string $message Message to output.
		 *
		 * @return void
		 */
		public static function line( string $message = '' ): void {
			self::$case->line[] = $message;
		}


		/**
		 * Store messages in this class.
		 *
		 * This and line() do the same thing.
		 *
		 * @param string $message Message to output.
		 *
		 * @return void
		 */
		public static function log( string $message = '' ): void {
			self::$case->line[] = $message;
		}


		/**
		 * Store messages in this class.
		 *
		 * @param string $message Message to output.
		 * @param boolean|integer   $exit    Whether to exit the script.
		 *
		 * @throws ExitException
		 * @return void
		 */
		public static function error( string $message = '', $exit = true ): void {
			self::$case->error[] = "Error: {$message}";
			$return_code = false;
            if ( true === $exit ) {
            	$return_code = 1;
            } elseif ( is_int( $exit ) && $exit >= 1 ) {
            	$return_code = $exit;
            }
			if ( false !== $return_code ) {
				throw new ExitException( '', $return_code );
			}
		}


		/**
		 * Store messages in this class.
		 *
		 * @param string $message Message to output.
		 *
		 * @return void
		 */
		public static function warning( string $message ): void {
			self::$case->warning[] = $message;
		}


		/**
		 * Currently a noop to prevent fatal errors during testing.
		 *
		 * @param string $command
		 * @param        $callback
		 *
		 * @return void
		 */
		public static function add_command( string $command, $callback ): void {
		}
	}
}


namespace WP_CLI {

	class ExitException extends \Exception {
	}
}


namespace cli\progress {

	class Bar {
		protected $_current = 0;

		protected $_interval;

		protected $_message;

		protected $_total = 0;


		/**
		 * Instantiates a Progress Notifier.
		 *
		 * @param string $msg      The text to display next to the Notifier.
		 * @param int    $total    The total number of ticks we will be performing.
		 * @param int    $interval The interval in milliseconds between updates.
		 */
		public function __construct( string $msg, int $total, int $interval = 100 ) {
			$this->_message = $msg;
			$this->_total = (int) $total;
			$this->_interval = (int) $interval;
		}


		/**
		 * This method is the meat of all Notifiers. First we increment the ticker
		 * and then update the display if enough time has passed since our last tick.
		 *
		 * @param int $increment The amount to increment by.
		 */
		public function tick( int $increment = 1 ): void {
			$this->_current += $increment;
		}


		public function finish(): void {
			$this->_current = $this->_total;
		}
	}
}

namespace WP_CLI\Utils {

	use cli\progress\Bar;

	function get_flag_value( array $assoc_args, string $flag, $default = null ) {
		return $assoc_args[ $flag ] ?? $default;
	}

	function format_items( string $format, array $items, $fields ) {
		if ( ! is_array( $fields ) ) {
			$fields = \array_map( 'trim', explode( ',', $fields ) );
		}
		\WP_CLI::line( "{$format}: " . \implode( ', ', $fields ) );
		foreach ( $items as $item ) {
			$picked = \array_intersect_key( $item, \array_flip( $fields ) );
			$sorted = \array_merge( \array_flip( $fields ), $picked );

			\WP_CLI::line( \implode( ', ', $sorted ) );
		}
	}

	function make_progress_bar( $message, $count, $interval = 100 ): Bar {
		return new Bar( $message, $count, $interval );
	}
}
