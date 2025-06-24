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

	/**
	 * @var ?\WP_Styles
	 */
	private $wp_styles = null;

	/**
	 * @var ?\WP
	 */
	private ?\WP $wp = null;

	/**
	 * @var \WP_Block_Type_Registry
	 */
	private \WP_Block_Type_Registry $wp_block_type_registry;


	private function __construct() {
		$this->backup_wp_filter();
		$this->wp_actions = $GLOBALS['wp_actions'];
		$this->wp_filters = $GLOBALS['wp_filters'];
		$this->wp_current_filter = $GLOBALS['wp_current_filter'];
		$this->wp_meta_boxes = $GLOBALS['wp_meta_boxes'] ?? null;
		$this->wp_meta_keys = $GLOBALS['wp_meta_keys'] ?? [];
		$this->wp_registered_settings = $GLOBALS['wp_registered_settings'] ?? [];
		$this->wp_block_type_registry = clone \WP_Block_Type_Registry::get_instance();

		if ( isset( $GLOBALS['wp'] ) && $GLOBALS['wp'] instanceof \WP ) {
			$this->wp = clone $GLOBALS['wp'];
		}

		if ( isset( $GLOBALS['wp_scripts'] ) && $GLOBALS['wp_scripts'] instanceof \WP_Scripts ) {
			$this->wp_scripts = clone( $GLOBALS['wp_scripts'] );
		}
		if ( isset( $GLOBALS['wp_styles'] ) && $GLOBALS['wp_styles'] instanceof \WP_Styles ) {
			$this->wp_styles = clone( $GLOBALS['wp_styles'] );
		}
	}


	/**
	 * @return \WP
	 */
	public function get_wp(): \WP {
		return $this->wp instanceof \WP ? clone $this->wp : new \WP();
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


	/**
	 * @return ?\WP_Styles
	 */
	public function get_wp_styles(): ?\WP_Styles {
		return $this->wp_styles instanceof \WP_Styles ? clone $this->wp_styles : null;
	}


	/**
	 * @return \WP_Block_Type_Registry
	 */
	public function get_wp_block_type_registry(): \WP_Block_Type_Registry {
		return clone $this->wp_block_type_registry;
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
