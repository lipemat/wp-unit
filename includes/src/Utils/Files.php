<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Utils;

use Lipe\WP_Unit\Traits\Singleton;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * File utilities.
 *
 * Use to be part of the `WP_UnitTestCase_Base` class.
 *
 * @author Mat Lipe
 * @since  4.0.0
 *
 */
class Files {
	use Singleton;

	/**
	 * Selectively deletes a file.
	 *
	 * Does not delete a file if its path is set in the `$ignore_files` property.
	 *
	 * @param string $file File path.
	 */
	public function unlink( string $file, array $ignore_files = [] ): void {
		$exists = is_file( $file );
		if ( $exists && ! \in_array( $file, $ignore_files, true ) ) {
			//error_log( $file );
			unlink( $file );
		} elseif ( ! $exists ) {
			$this->fail( "Trying to delete a file that doesn't exist: $file" );
		}
	}


	/**
	 * Selectively deletes files from a directory.
	 *
	 * Does not delete files if their paths are set in the `$ignore_files` property.
	 *
	 * @since 4.0.0
	 *
	 * @param string $path Directory path.
	 */
	public function rmdir( string $path, array $ignore_files = [] ): void {
		$files = $this->files_in_dir( $path );
		foreach ( $files as $file ) {
			if ( ! \in_array( $file, $ignore_files, true ) ) {
				$this->unlink( $file, $ignore_files );
			}
		}
	}


	/**
	 * Returns a list of all files contained inside a directory.
	 *
	 * @since 4.0.0
	 *
	 * @param string $dir Path to the directory to scan.
	 *
	 * @return array List of file paths.
	 */
	public function files_in_dir( string $dir ): array {
		$files = [];

		$iterator = new RecursiveDirectoryIterator( $dir );
		$objects = new RecursiveIteratorIterator( $iterator );
		foreach ( $objects as $name => $object ) {
			if ( is_file( $name ) ) {
				$files[] = $name;
			}
		}

		return $files;
	}


	/**
	 * Touches the given file and its directory if it doesn't already exist.
	 *
	 * This can be used to ensure a file that is implictly relied on in a test exists
	 * without it having to be built.
	 *
	 * @param string $file The file name.
	 */
	public static function touch( $file ) {
		if ( file_exists( $file ) ) {
			return;
		}

		$dir = \dirname( $file );

		if ( ! file_exists( $dir ) && ! mkdir( $dir, 0777, true ) && ! is_dir( $dir ) ) {
			throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $dir ) );
		}

		touch( $file );
	}


	/**
	 * Creates an attachment post from an uploaded file.
	 *
	 * @since 4.4.0
	 * @since 6.2.0 Returns a WP_Error object on failure.
	 *
	 * @param array $upload         Array of information about the uploaded file, provided by wp_upload_bits().
	 * @param int   $parent_post_id Optional. Parent post ID.
	 *
	 * @return int|\WP_Error The attachment ID on success, WP_Error object on failure.
	 */
	public function make_attachment( $upload, $parent_post_id = 0 ) {
		$type = '';
		if ( ! empty( $upload['type'] ) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ( $mime ) {
				$type = $mime['type'];
			}
		}

		$attachment = [
			'post_title'     => wp_basename( $upload['file'] ),
			'post_content'   => '',
			'post_type'      => 'attachment',
			'post_parent'    => $parent_post_id,
			'post_mime_type' => $type,
			'guid'           => $upload['url'],
		];

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id, true );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
		);

		return $attachment_id;
	}
}
