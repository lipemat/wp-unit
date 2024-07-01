<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

use Lipe\WP_Unit\Traits\Singleton;

/**
 * @author Mat Lipe
 * @since  4.0.0
 *
 */
class Cleanup {
	use Singleton;

	/**
	 * Cleans the global scope (e.g `$_GET` and `$_POST`).
	 */
	public function clean_up_global_scope() {
		$_GET = [];
		$_POST = [];
		$_REQUEST = [];
		$this->flush_cache();
	}


	/**
	 * Resets `$_SERVER` variables
	 */
	public function reset__SERVER() {
		tests_reset__SERVER();
	}


	/**
	 * Unregisters non-built-in post statuses.
	 */
	public function reset_post_statuses() {
		foreach ( get_post_stati( [ '_builtin' => false ] ) as $post_status ) {
			_unregister_post_status( $post_status );
		}
	}


	/**
	 * Unregisters existing taxonomies and register defaults.
	 *
	 * Run before each test to clean up the global scope, in case
	 * a test forgets to unregister a taxonomy on its own, or fails before
	 * it has a chance to do so.
	 */
	public function reset_taxonomies(): void {
		foreach ( get_taxonomies() as $tax ) {
			_unregister_taxonomy( $tax );
		}
		create_initial_taxonomies();
	}


	/**
	 * Unregisters existing post types and register defaults.
	 *
	 * Run before each test to clean up the global scope, in case
	 * a test forgets to unregister a post type on its own, or fails before
	 * it has a chance to do so.
	 */
	public function reset_post_types(): void {
		foreach ( get_post_types( [], 'objects' ) as $pt ) {
			_unregister_post_type( $pt->name );
		}
		create_initial_post_types();
	}


	/**
	 * Reset the lazy load meta queue.
	 */
	public function reset_lazyload_queue(): void {
		$lazyloader = wp_metadata_lazyloader();
		$lazyloader->reset_queue( 'term' );
		$lazyloader->reset_queue( 'comment' );
		$lazyloader->reset_queue( 'blog' );
	}


	/**
	 * Flushes the WordPress object cache.
	 */
	public function flush_cache(): void {
		global $wp_object_cache;

		if ( \function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' ) ) {
			wp_cache_flush_runtime();
		}

		if ( \is_object( $wp_object_cache ) && method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset();
		}

		wp_cache_flush();

		wp_cache_add_global_groups(
			[
				'blog-details',
				'blog-id-cache',
				'blog-lookup',
				'blog_meta',
				'global-posts',
				'networks',
				'network-queries',
				'sites',
				'site-details',
				'site-options',
				'site-queries',
				'site-transient',
				'theme_files',
				'rss',
				'users',
				'user-queries',
				'user_meta',
				'useremail',
				'userlogins',
				'userslugs',
			]
		);

		wp_cache_add_non_persistent_groups( [ 'counts', 'plugins', 'theme_json' ] );
	}


	/**
	 * Resets permalinks and flushes rewrites.
	 *
	 * @since 4.4.0
	 *
	 * @global \WP_Rewrite $wp_rewrite
	 *
	 * @param string      $structure Optional. Permalink structure to set. Default empty.
	 */
	public function set_permalink_structure( string $structure = '' ): void {
		global $wp_rewrite;
		if ( ! $wp_rewrite->permalink_structure ) {
			return;
		}

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $structure );
		$wp_rewrite->flush_rules();
	}

	/**
	 * Cleans up any registered meta keys.
	 *
	 * @notice When not running core tests, the meta keys are restored via
	 *         `$this->_restore_hooks` so this method does nothing.
	 *
	 * @since  5.1.0
	 *
	 * @global array $wp_meta_keys
	 */
	public function unregister_all_meta_keys(): void {
		global $wp_meta_keys;
		if ( ! \is_array( $wp_meta_keys ) ) {
			return;
		}
		foreach ( $wp_meta_keys as $object_type => $type_keys ) {
			foreach ( $type_keys as $object_subtype => $subtype_keys ) {
				foreach ( $subtype_keys as $key => $value ) {
					unregister_meta_key( $object_type, $key, $object_subtype );
				}
			}
		}
	}
}
