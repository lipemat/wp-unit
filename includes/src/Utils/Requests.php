<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Utils;

use Lipe\WP_Unit\Traits\Singleton;

/**
 * Utilities for working in HTTP requests.
 *
 * @author Mat Lipe
 * @since  3.7.0
 *
 */
final class Requests {
	use Singleton;

	/**
	 * Convert JSON data into a raw request response.
	 *
	 * For use when mocking responses.
	 *
	 * @see \WP_Http_Remote_Post_TestCase::mock_response()
	 *
	 * @param array|\JsonSerializable $data
	 *
	 * @return string
	 */
	public function json_response( $data ): string {
		return 'HTTP/1.1 200 OK
				Content-Type: application/json; charset=UTF-8'
		       . "\r\n\r\n" .
		       wp_json_encode( $data );
	}


	/**
	 * Convert HTML string into a raw request response.
	 *
	 * For use when mocking responses.
	 *
	 * @see \WP_Http_Remote_Post_TestCase::mock_response()
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function html_response( string $html ): string {
		return 'HTTP/1.1 200 OK
			   Content-Type: text/html; charset=UTF-8'
		       . "\r\n\r\n" .
		       '<!DOCTYPE html />' . $html;
	}


	/**
	 * Resets permalinks and flushes rewrites.
	 *
	 * @since 4.4.0
	 *
	 * @global \WP_Rewrite $wp_rewrite
	 *
	 * @param string       $structure Optional. Permalink structure to set. Default empty.
	 */
	public function set_permalink_structure( string $structure = '' ): void {
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $structure );
		$wp_rewrite->flush_rules();
	}
}
