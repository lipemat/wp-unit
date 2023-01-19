<?php

/**
 * Base for testing the object cache.
 *
 * @author Mat Lipe
 * @since  September 2022
 *
 */
class Object_Cache_TestCase extends \WP_UnitTestCase {
	/**
	 * @var \WP_Object_Cache
	 */
	public $object_cache;


	public function set_up() {
		parent::set_up();
		global $wp_object_cache;

		$cache_class = get_class( $wp_object_cache );
		$wp_object_cache = new $cache_class();
		$this->object_cache = $wp_object_cache;
		$this->object_cache->flush();
	}


	public function tear_down() {
		$this->object_cache->cache = [];
		if ( method_exists( $this->object_cache, '__remoteset' ) ) {
			$this->object_cache->__remoteset();
		}
		$this->object_cache->flush();

		parent::tear_down();
	}


	/**
	 * Get the parsed key for the object cache.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return string
	 */
	protected function get_cache_key( $key, $group = 'default' ) {
		return $this->object_cache->key( $key, $group );
	}


	/**
	 * Test the return of a value from first the object `cache` property,
	 * then the external cache handler.
	 *
	 * @param string|int $key
	 * @param mixed      $value
	 * @param string     $group
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	protected function assertCachePropertyAndExternal( $key, $value, $group = 'default' ) {
		$built_key = $this->get_cache_key( $key, $group );
		// Verify correct value and type is returned.
		$this->assertSame( $value, $this->object_cache->get( $key, $group ) );
		$this->assertSame( $value, $this->object_cache->cache[ $built_key ] );
		unset( $this->object_cache->cache[ $built_key ] );
		$this->assertSame( $value, $this->object_cache->get( $key, $group ) );
	}


	/**
	 * Assert a key does exist in the external object cache.
	 *
	 * @since 3.2.0
	 */
	protected function assertCacheExternal( $key, $group = 'default' ) {
		$built_key = $this->get_cache_key( $key, $group );
		unset( $this->object_cache->cache[ $built_key ] );
		$this->assertNotFalse( $this->object_cache->get( $key, $group ) );
	}


	/**
	 * Assert a key does not exist in the external object object.
	 *
	 * @since 3.2.0
	 */
	public function assertNotCacheExternal( $key, $group = 'default' ) {
		$built_key = $this->get_cache_key( $key, $group );
		unset( $this->object_cache->cache[ $built_key ] );
		$this->assertFalse( $this->object_cache->get( $key, $group ) );
	}


	/**
	 * @deprecated  In favor of assertCachePropertyAndExternal
	 */
	protected function assert_cache_local_and_external( $key, $value, $group = 'default' ) {
		_deprecated_function( __METHOD__, '3.2.0', 'assertCachePropertyAndExternal' );
		$this->assertCachePropertyAndExternal( $key, $value, $group );
	}

}
