<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * Hold the state of hooks and other globals, so they be restored at the appropriate time.
 *
 * @author Mat Lipe
 * @since  3.6.0
 *
 * @phpstan-type META_BOXES array<string, array<string, array<string, array<string, array{
 *     callback: callable,
 *     id: string,
 *     title: string,
 *     args: array<mixed>
 * }|false>>>>
 * @phpstan-type META_KEYS array<string, array<string, array<string, array<string, bool|null|string>>>>
 */
final class Hook_State {
	/**
	 * @var array<string, int>
	 */
	private $wp_actions;

	/**
	 * @var array<string, \WP_Hook>
	 */
	private $wp_filter = [];

	/**
	 * @var array<string, \WP_Hook>
	 */
	private $wp_filters;

	/**
	 * @var array<int, string>
	 */
	private $wp_current_filter;

	/**
	 * @phpstan-var META_BOXES|null
	 */
	private $wp_meta_boxes;

	/**
	 * @phpstan-var META_KEYS
	 */
	private $wp_meta_keys;

	/**
	 * @var array<string, array<string, string|false>>
	 */
	private $wp_registered_settings;

	/**
	 * @var ?\WP_Scripts
	 */
	private $wp_scripts = null;


	private function __construct() {
		$this->backup_wp_filter();
		$this->wp_actions = $GLOBALS['wp_actions'];
		$this->wp_filters = $GLOBALS['wp_filters'];
		$this->wp_current_filter = $GLOBALS['wp_current_filter'];
		$this->wp_meta_boxes = $GLOBALS['wp_meta_boxes'] ?? null;
		$this->wp_meta_keys = $GLOBALS['wp_meta_keys'] ?? [];
		$this->wp_registered_settings = $GLOBALS['wp_registered_settings'] ?? [];
		if ( isset( $GLOBALS['wp_scripts'] ) && $GLOBALS['wp_scripts'] instanceof \WP_Scripts ) {
			$this->wp_scripts = clone( $GLOBALS['wp_scripts'] );
		}
	}


	/**
	 * @return array<string, int>
	 */
	public function get_wp_actions(): array {
		return $this->wp_actions;
	}


	/**
	 * @return array<string, \WP_Hook>
	 */
	public function get_wp_filters(): array {
		return $this->wp_filters;
	}


	/**
	 * @return array<int, string>
	 */
	public function get_wp_current_filter(): array {
		return $this->wp_current_filter;
	}


	/**
	 * @phpstan-return META_BOXES
	 */
	public function get_wp_meta_boxes(): ?array {
		return $this->wp_meta_boxes;
	}


	/**
	 * @phpstan-return META_KEYS
	 */
	public function get_wp_meta_keys(): array {
		return $this->wp_meta_keys;
	}


	/**
	 * @return array<string, array<string, string|false>>
	 */
	public function get_wp_registered_settings(): array {
		return $this->wp_registered_settings;
	}


	/**
	 * @return ?\WP_Scripts
	 */
	public function get_wp_scripts(): ?\WP_Scripts {
		return $this->wp_scripts instanceof \WP_Scripts ? clone $this->wp_scripts : null;
	}


	public function restore_wp_filter(): void {
		$GLOBALS['wp_filter'] = [];
		foreach ( $this->wp_filter as $hook_name => $hook_object ) {
			$GLOBALS['wp_filter'][ $hook_name ] = clone $hook_object;
		}
	}


	/**
	 * Used to provide the array shape for backwards compatibility.
	 *
	 * @return array{
	 *      wp_actions?: array,
	 *      wp_filter?: array,
	 *      wp_filters?: array,
	 *      wp_current_filter?: array,
	 *      wp_meta_keys?: array,
	 *      wp_registered_settings?: array
	 * }
	 */
	public function get_legacy_hooks(): array {
		return [
			'wp_actions'             => $this->wp_actions,
			'wp_filter'              => $this->wp_filter,
			'wp_filters'             => $this->wp_filters,
			'wp_current_filter'      => $this->wp_current_filter,
			'wp_meta_keys'           => $this->wp_meta_keys,
			'wp_registered_settings' => $this->wp_registered_settings,
		];
	}


	private function backup_wp_filter(): void {
		foreach ( $GLOBALS['wp_filter'] as $hook_name => $hook_object ) {
			$this->wp_filter[ $hook_name ] = clone $hook_object;
		}
	}


	public static function factory(): self {
		return new self();
	}
}
