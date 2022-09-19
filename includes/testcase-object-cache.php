<?php

/**
 * Base for testing the object cache.
 *
 * @author Mat Lipe
 * @since  September 2022
 *
 */
class Object_Cache_TestCase extends \WP_UnitTestCase {
	public $object_cache;


	public function set_up() {
		parent::set_up();
		global $wp_object_cache;

		$cache_class = get_class( $wp_object_cache );
		$this->object_cache = new $cache_class();
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
	 * Test the return of a value from first the local `$cache`,
	 * then the external cache handler.
	 *
	 * @param string|int $key
	 * @param mixed      $value
	 * @param string     $group
	 *
	 * @return void
	 */
	protected function test_cache_local_and_external( $key, $value, $group = 'default' ) {
		$built_key = $this->object_cache->key( $key, $group );
		// Verify correct value and type is returned.
		$this->assertSame( $value, $this->object_cache->get( $key, $group ) );
		$this->assertSame( $value, $this->object_cache->cache[ $built_key ] );
		unset( $this->object_cache->cache[ $built_key ] );
		$this->assertSame( $value, $this->object_cache->get( $key, $group ) );
	}
}
