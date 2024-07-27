<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Traits;

use Lipe\WP_Unit\Utils\Files;

/**
 * Remove any files uploaded during tests.
 *
 * Originally part fo the `WP_UnitTestCase_Base` class but only used in the `WP_XMLRPC_UnitTestCase` class.
 *
 * @author Mat Lipe
 * @since  4.0.0
 *
 */
trait RemoveUploaded {
	/**
	 * @var string[]|null
	 */
	protected static ?array $ignore_files = null;


	public function set_up() {
		parent::set_up();
		if ( null === static::$ignore_files ) {
			// Only scan the directory once per test run.
			static::$ignore_files = $this->scan_user_uploads();
		}
	}


	public function tear_down(): void {
		$this->remove_added_uploads();
		parent::tear_down();
	}


	/**
	 * Deletes files added to the `uploads` directory during tests.
	 *
	 * This method works in tandem with the `set_up()` and `rmdir()` methods:
	 * - `set_up()` scans the `uploads` directory before every test, and stores
	 *   its contents inside the `$ignore_files` property.
	 * - `rmdir()` and its helper methods only delete files that are not listed
	 *   in the `$ignore_files` property. If called during `tear_down()` in tests,
	 *   this will only delete files added during the previously run test.
	 */
	public function remove_added_uploads(): void {
		$uploads = wp_upload_dir();
		if ( null === static::$ignore_files ) {
			static::$ignore_files = $this->scan_user_uploads();
		}

		Files::instance()->rmdir( $uploads['basedir'], static::$ignore_files );
	}


	/**
	 * Returns a list of all files contained inside the `uploads` directory.
	 *
	 * @since 4.0.0
	 *
	 * @return string[] List of file paths.
	 */
	public function scan_user_uploads(): array {
		static $files = [];
		if ( [] !== $files ) {
			return $files;
		}

		$uploads = wp_upload_dir();
		return Files::instance()->files_in_dir( $uploads['basedir'] );
	}
}
