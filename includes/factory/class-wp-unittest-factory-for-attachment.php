<?php

use Lipe\WP_Unit\Traits\RemoveUploaded;

/**
 * Unit test factory for attachments.
 *
 * Note: The below @method notations are defined solely for the benefit of IDEs,
 * as a way to indicate expected return values from the given factory methods.
 *
 * @method int|WP_Error     create( $args = [], array $generation_definitions = null )
 * @method WP_Post|WP_Error create_and_get( array $args = [], array $generation_definitions = null )
 * @method ( int|WP_Error )[] create_many( $count, $args = array(), $generation_definitions = null )
 */
class WP_UnitTest_Factory_For_Attachment extends WP_UnitTest_Factory_For_Post {
	protected $test_file;


	/**
	 * Create an attachment fixture.
	 *
	 * @since 1.8.0 (Automatically generate file to go with attachment)
	 *
	 * Array of arguments. Accepts all arguments that can be passed to
	 * `wp_insert_attachment()`, in addition to the following:
	 *
	 * @param array $args        {
	 *
	 * @type int    $post_parent ID of the post to which the attachment belongs.
	 * @type string $file        Path of the attached file.
	 *                           }
	 * @return int|WP_Error The attachment ID on success, WP_Error object on failure.
	 */
	public function create_object( array $args ) {
		$r = array_merge(
			[
				'file'        => '',
				'post_parent' => 0,
			],
			$args
		);

		// @since 1.8.0
		if ( ! isset( $args['file'] ) ) {
			$this->test_file = trailingslashit( wp_get_upload_dir()['basedir'] ) . 'test-image.jpg';
			\copy( DIR_TEST_IMAGES . '/test-image.jpg', $this->test_file );
			$r['file'] = $this->test_file;
			$r['post_mime_type'] = 'image/jpg';
		}

		return wp_insert_attachment( $r, $r['file'], $r['post_parent'], true );
	}


	/**
	 * Converts a file into an attachment object.
	 * - Uploads file to the uploads directory.
	 * - Creates an attachment post object.
	 * - Generates the attachment metadata (e.g. image sizes).
	 *
	 * @see RemoveUploaded for cleanup of uploaded files.
	 *
	 * @param string $file The file path and name to create attachment object for.
	 * @param int    $parent_post_id ID of the post to attach the file to.
	 *
	 * @return int|\WP_Error The attachment ID on success, \WP_Error object on failure.
	 */
	public function create_upload_object( string $file, int $parent_post_id = 0 ) {
		$contents = \file_get_contents( $file );
		$upload = wp_upload_bits( wp_basename( $file ), null, $contents );

		$type = '';
		if ( '' !== $upload['type'] ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ( false !== $mime['type'] ) {
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

		// Save the data.
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
