<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Utils;

use Lipe\WP_Unit\Traits\TestsSingleton;

/**
 * @author Mat Lipe
 * @since  4.11.0
 *
 */
final class Strings {
	use TestsSingleton;

	/**
	 * Trim whitespace from a string or array of strings.
	 *
	 * @param mixed     $value The value to trim.
	 * @param string    $glue  The separator to use imploding the array of strings back together.
	 *
	 * @return mixed
	 *
	 * @phpstan-template T
	 * @phpstan-param T $value
	 * @phpstan-return (T is array ? array<key-of<T>, mixed> : (T is object ? T : mixed))
	 */
	public function trim_deep( $value, string $glue = "\n" ) {
		if ( null === $value ) {
			return '';
		}
		$trim_whitespace = static function( $value ) use ( $glue ) {
			$value = \trim( $value );
			return \implode( $glue, \array_map( function( $value ) {
				return \trim( \str_replace( "\r\n", "\n", $value ) );
			}, \explode( "\n", $value ) ) );
		};

		return map_deep( $value, static function( $value ) use ( $trim_whitespace ) {
			if ( \is_string( $value ) ) {
				return $trim_whitespace( $value );
			}
			return $value;
		} );
	}
}
