<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Rules;

/**
 * Rules for a WP_Http transport class.
 *
 * @author Mat Lipe
 * @since  3.7.0
 *
 */
interface Http_Transport {
	public function request( string $url, ...$args ): string;


	public function request_multiple( array $requests ): array;


	public static function test(): bool;
}
