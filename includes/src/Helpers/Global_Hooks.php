<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

use Lipe\WP_Unit\Traits\Singleton;

/**
 * Global hooks and other global state management.
 *
 * @author Mat Lipe
 * @since  3.6.0
 *
 */
final class Global_Hooks {
	use Singleton;

	/**
	 * Hold the original state of the global hooks.
	 *
	 * @var Hook_State
	 */
	private $global_hooks;

	private const TYPES = [
		'wp_actions',
		'wp_filter',
		'wp_filters',
		'wp_current_filter',
		'wp_meta_keys',
		'wp_registered_sidebars',
	];


	/**
	 * Hook up the class.
	 *
	 * @return void
	 */
	protected function hook(): void {
		$this->global_hooks = Hook_State::factory();
	}


	/**
	 * Restore the original hooks before any tests were run.
	 *
	 * @return void
	 */
	public function restore_globals(): void {
		$this->restore_hooks( $this->global_hooks );
	}


	/**
	 * Restore the global hooks to the provided state.
	 *
	 * @param Hook_State $hooks
	 *
	 * @return void
	 */
	public function restore_hooks( Hook_State $hooks ): void {
		$hooks->restore_wp_filter();
		$GLOBALS['wp_actions'] = $hooks->get_wp_actions();
		$GLOBALS['wp_filters'] = $hooks->get_wp_filters();
		$GLOBALS['wp_current_filter'] = $hooks->get_wp_current_filter();
		$GLOBALS['wp_meta_keys'] = $hooks->get_wp_meta_keys();
		$GLOBALS['wp_registered_sidebars'] = $hooks->get_wp_registered_sidebars();
	}
}
