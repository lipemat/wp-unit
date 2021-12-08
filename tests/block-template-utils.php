<?php
/**
 * Tests_Block_Template_Utils class
 *
 * @package WordPress
 */

/**
 * Tests for the Block Templates abstraction layer.
 *
 * @group block-templates
 */
class Tests_Block_Template_Utils extends WP_UnitTestCase {
	private static $post;
	private static $template_part_post;
	private static $test_theme = 'block-theme';

	public static function wpSetUpBeforeClass() {
		// Set up a template post corresponding to a different theme.
		// We do this to ensure resolution and slug creation works as expected,
		// even with another post of that same name present for another theme.
		$args       = array(
			'post_type'    => 'wp_template',
			'post_name'    => 'my_template',
			'post_title'   => 'My Template',
			'post_content' => 'Content',
			'post_excerpt' => 'Description of my template',
			'tax_input'    => array(
				'wp_theme' => array(
					'this-theme-should-not-resolve',
				),
			),
		);
		self::$post = self::factory()->post->create_and_get( $args );
		wp_set_post_terms( self::$post->ID, 'this-theme-should-not-resolve', 'wp_theme' );

		// Set up template post.
		$args       = array(
			'post_type'    => 'wp_template',
			'post_name'    => 'my_template',
			'post_title'   => 'My Template',
			'post_content' => 'Content',
			'post_excerpt' => 'Description of my template',
			'tax_input'    => array(
				'wp_theme' => array(
					self::$test_theme,
				),
			),
		);
		self::$post = self::factory()->post->create_and_get( $args );
		wp_set_post_terms( self::$post->ID, self::$test_theme, 'wp_theme' );

		// Set up template part post.
		$template_part_args       = array(
			'post_type'    => 'wp_template_part',
			'post_name'    => 'my_template_part',
			'post_title'   => 'My Template Part',
			'post_content' => 'Content',
			'post_excerpt' => 'Description of my template part',
			'tax_input'    => array(
				'wp_theme'              => array(
					self::$test_theme,
				),
				'wp_template_part_area' => array(
					WP_TEMPLATE_PART_AREA_HEADER,
				),
			),
		);
		self::$template_part_post = self::factory()->post->create_and_get( $template_part_args );
		wp_set_post_terms( self::$template_part_post->ID, WP_TEMPLATE_PART_AREA_HEADER, 'wp_template_part_area' );
		wp_set_post_terms( self::$template_part_post->ID, self::$test_theme, 'wp_theme' );
	}

	public function set_up() {
		parent::set_up();
		switch_theme( self::$test_theme );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$post->ID );
	}

	public function test_build_block_template_result_from_post() {
		$template = _build_block_template_result_from_post(
			self::$post,
			'wp_template'
		);

		$this->assertNotWPError( $template );
		$this->assertSame( get_stylesheet() . '//my_template', $template->id );
		$this->assertSame( get_stylesheet(), $template->theme );
		$this->assertSame( 'my_template', $template->slug );
		$this->assertSame( 'publish', $template->status );
		$this->assertSame( 'custom', $template->source );
		$this->assertSame( 'My Template', $template->title );
		$this->assertSame( 'Description of my template', $template->description );
		$this->assertSame( 'wp_template', $template->type );

		// Test template parts.
		$template_part = _build_block_template_result_from_post(
			self::$template_part_post,
			'wp_template_part'
		);
		$this->assertNotWPError( $template_part );
		$this->assertSame( get_stylesheet() . '//my_template_part', $template_part->id );
		$this->assertSame( get_stylesheet(), $template_part->theme );
		$this->assertSame( 'my_template_part', $template_part->slug );
		$this->assertSame( 'publish', $template_part->status );
		$this->assertSame( 'custom', $template_part->source );
		$this->assertSame( 'My Template Part', $template_part->title );
		$this->assertSame( 'Description of my template part', $template_part->description );
		$this->assertSame( 'wp_template_part', $template_part->type );
		$this->assertSame( WP_TEMPLATE_PART_AREA_HEADER, $template_part->area );
	}

	function test_build_block_template_result_from_file() {
		$template = _build_block_template_result_from_file(
			array(
				'slug' => 'single',
				'path' => __DIR__ . '/../data/templates/template.html',
			),
			'wp_template'
		);

		$this->assertSame( get_stylesheet() . '//single', $template->id );
		$this->assertSame( get_stylesheet(), $template->theme );
		$this->assertSame( 'single', $template->slug );
		$this->assertSame( 'publish', $template->status );
		$this->assertSame( 'theme', $template->source );
		$this->assertSame( 'Single Post', $template->title );
		$this->assertSame( 'Displays a single post.', $template->description );
		$this->assertSame( 'wp_template', $template->type );

		// Test template parts.
		$template_part = _build_block_template_result_from_file(
			array(
				'slug' => 'header',
				'path' => __DIR__ . '/../data/templates/template.html',
				'area' => WP_TEMPLATE_PART_AREA_HEADER,
			),
			'wp_template_part'
		);
		$this->assertSame( get_stylesheet() . '//header', $template_part->id );
		$this->assertSame( get_stylesheet(), $template_part->theme );
		$this->assertSame( 'header', $template_part->slug );
		$this->assertSame( 'publish', $template_part->status );
		$this->assertSame( 'theme', $template_part->source );
		$this->assertSame( 'header', $template_part->title );
		$this->assertSame( '', $template_part->description );
		$this->assertSame( 'wp_template_part', $template_part->type );
		$this->assertSame( WP_TEMPLATE_PART_AREA_HEADER, $template_part->area );
	}

	function test_inject_theme_attribute_in_block_template_content() {
		$theme                           = get_stylesheet();
		$content_without_theme_attribute = '<!-- wp:template-part {"slug":"header","align":"full", "tagName":"header","className":"site-header"} /-->';
		$template_content                = _inject_theme_attribute_in_block_template_content(
			$content_without_theme_attribute,
			$theme
		);
		$expected                        = sprintf(
			'<!-- wp:template-part {"slug":"header","align":"full","tagName":"header","className":"site-header","theme":"%s"} /-->',
			get_stylesheet()
		);
		$this->assertSame( $expected, $template_content );

		$content_without_theme_attribute_nested = '<!-- wp:group --><!-- wp:template-part {"slug":"header","align":"full", "tagName":"header","className":"site-header"} /--><!-- /wp:group -->';
		$template_content                       = _inject_theme_attribute_in_block_template_content(
			$content_without_theme_attribute_nested,
			$theme
		);
		$expected                               = sprintf(
			'<!-- wp:group --><!-- wp:template-part {"slug":"header","align":"full","tagName":"header","className":"site-header","theme":"%s"} /--><!-- /wp:group -->',
			get_stylesheet()
		);
		$this->assertSame( $expected, $template_content );

		// Does not inject theme when there is an existing theme attribute.
		$content_with_existing_theme_attribute = '<!-- wp:template-part {"slug":"header","theme":"fake-theme","align":"full", "tagName":"header","className":"site-header"} /-->';
		$template_content                      = _inject_theme_attribute_in_block_template_content(
			$content_with_existing_theme_attribute,
			$theme
		);
		$this->assertSame( $content_with_existing_theme_attribute, $template_content );

		// Does not inject theme when there is no template part.
		$content_with_no_template_part = '<!-- wp:post-content /-->';
		$template_content              = _inject_theme_attribute_in_block_template_content(
			$content_with_no_template_part,
			$theme
		);
		$this->assertSame( $content_with_no_template_part, $template_content );
	}

	/**
	 * @ticket 54448
	 *
	 * @dataProvider data_remove_theme_attribute_in_block_template_content
	 */
	function test_remove_theme_attribute_in_block_template_content( $template_content, $expected ) {
		$this->assertEquals( $expected, _remove_theme_attribute_in_block_template_content( $template_content ) );
	}

	function data_remove_theme_attribute_in_block_template_content() {
		return array(
			array(
				'<!-- wp:template-part {"slug":"header","theme":"tt1-blocks","align":"full","tagName":"header","className":"site-header"} /-->',
				'<!-- wp:template-part {"slug":"header","align":"full","tagName":"header","className":"site-header"} /-->',
			),
			array(
				'<!-- wp:group --><!-- wp:template-part {"slug":"header","theme":"tt1-blocks","align":"full","tagName":"header","className":"site-header"} /--><!-- /wp:group -->',
				'<!-- wp:group --><!-- wp:template-part {"slug":"header","align":"full","tagName":"header","className":"site-header"} /--><!-- /wp:group -->',
			),
			// Does not modify content when there is no existing theme attribute.
			array(
				'<!-- wp:template-part {"slug":"header","align":"full","tagName":"header","className":"site-header"} /-->',
				'<!-- wp:template-part {"slug":"header","align":"full","tagName":"header","className":"site-header"} /-->',
			),
			// Does not remove theme when there is no template part.
			array(
				'<!-- wp:post-content /-->',
				'<!-- wp:post-content /-->',
			),
		);
	}

	/**
	 * Should retrieve the template from the theme files.
	 */
	function test_get_block_template_from_file() {
		$id       = get_stylesheet() . '//' . 'index';
		$template = get_block_template( $id, 'wp_template' );
		$this->assertSame( $id, $template->id );
		$this->assertSame( get_stylesheet(), $template->theme );
		$this->assertSame( 'index', $template->slug );
		$this->assertSame( 'publish', $template->status );
		$this->assertSame( 'theme', $template->source );
		$this->assertSame( 'wp_template', $template->type );

		// Test template parts.
		$id       = get_stylesheet() . '//' . 'small-header';
		$template = get_block_template( $id, 'wp_template_part' );
		$this->assertSame( $id, $template->id );
		$this->assertSame( get_stylesheet(), $template->theme );
		$this->assertSame( 'small-header', $template->slug );
		$this->assertSame( 'publish', $template->status );
		$this->assertSame( 'theme', $template->source );
		$this->assertSame( 'wp_template_part', $template->type );
		$this->assertSame( WP_TEMPLATE_PART_AREA_HEADER, $template->area );
	}

	/**
	 * Should retrieve the template from the CPT.
	 */
	public function test_get_block_template_from_post() {
		$id       = get_stylesheet() . '//' . 'my_template';
		$template = get_block_template( $id, 'wp_template' );
		$this->assertSame( $id, $template->id );
		$this->assertSame( get_stylesheet(), $template->theme );
		$this->assertSame( 'my_template', $template->slug );
		$this->assertSame( 'publish', $template->status );
		$this->assertSame( 'custom', $template->source );
		$this->assertSame( 'wp_template', $template->type );

		// Test template parts.
		$id       = get_stylesheet() . '//' . 'my_template_part';
		$template = get_block_template( $id, 'wp_template_part' );
		$this->assertSame( $id, $template->id );
		$this->assertSame( get_stylesheet(), $template->theme );
		$this->assertSame( 'my_template_part', $template->slug );
		$this->assertSame( 'publish', $template->status );
		$this->assertSame( 'custom', $template->source );
		$this->assertSame( 'wp_template_part', $template->type );
		$this->assertSame( WP_TEMPLATE_PART_AREA_HEADER, $template->area );
	}

	/**
	 * Should retrieve block templates (file and CPT)
	 */
	public function test_get_block_templates() {
		function get_template_ids( $templates ) {
			return array_map(
				static function( $template ) {
					return $template->id;
				},
				$templates
			);
		}

		// All results.
		$templates    = get_block_templates( array(), 'wp_template' );
		$template_ids = get_template_ids( $templates );

		// Avoid testing the entire array because the theme might add/remove templates.
		$this->assertContains( get_stylesheet() . '//' . 'my_template', $template_ids );

		// The result might change in a block theme.
		// $this->assertContains( get_stylesheet() . '//' . 'index', $template_ids );

		// Filter by slug.
		$templates    = get_block_templates( array( 'slug__in' => array( 'my_template' ) ), 'wp_template' );
		$template_ids = get_template_ids( $templates );
		$this->assertSame( array( get_stylesheet() . '//' . 'my_template' ), $template_ids );

		// Filter by CPT ID.
		$templates    = get_block_templates( array( 'wp_id' => self::$post->ID ), 'wp_template' );
		$template_ids = get_template_ids( $templates );
		$this->assertSame( array( get_stylesheet() . '//' . 'my_template' ), $template_ids );

		// Filter template part by area.
		// Requires a block theme.
		/*$templates    = get_block_templates( array( 'area' => WP_TEMPLATE_PART_AREA_HEADER ), 'wp_template_part' );
		$template_ids = get_template_ids( $templates );
		$this->assertSame(
			array(
				get_stylesheet() . '//' . 'my_template_part',
				get_stylesheet() . '//' . 'header',
			),
			$template_ids
		);
		*/
	}

	/**
	 * Should flatten nested blocks
	 */
	function test_flatten_blocks() {
		$content_template_part_inside_group = '<!-- wp:group --><!-- wp:template-part {"slug":"header"} /--><!-- /wp:group -->';
		$blocks                             = parse_blocks( $content_template_part_inside_group );
		$actual                             = _flatten_blocks( $blocks );
		$expected                           = array( $blocks[0], $blocks[0]['innerBlocks'][0] );
		$this->assertSame( $expected, $actual );

		$content_template_part_inside_group_inside_group = '<!-- wp:group --><!-- wp:group --><!-- wp:template-part {"slug":"header"} /--><!-- /wp:group --><!-- /wp:group -->';
		$blocks   = parse_blocks( $content_template_part_inside_group_inside_group );
		$actual   = _flatten_blocks( $blocks );
		$expected = array( $blocks[0], $blocks[0]['innerBlocks'][0], $blocks[0]['innerBlocks'][0]['innerBlocks'][0] );
		$this->assertSame( $expected, $actual );

		$content_without_inner_blocks = '<!-- wp:group /-->';
		$blocks                       = parse_blocks( $content_without_inner_blocks );
		$actual                       = _flatten_blocks( $blocks );
		$expected                     = array( $blocks[0] );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Should generate block templates export file.
	 *
	 * @ticket 54448
	 */
	function test_wp_generate_block_templates_export_file() {
		$filename = wp_generate_block_templates_export_file();
		$this->assertFileExists( $filename, 'zip file is created at the specified path' );
		$this->assertTrue( filesize( $filename ) > 0, 'zip file is larger than 0 bytes' );

		// Open ZIP file and make sure the directories exist.
		$zip = new ZipArchive();
		$zip->open( $filename );
		$has_theme_dir                = $zip->locateName( 'theme/' ) !== false;
		$has_block_templates_dir      = $zip->locateName( 'theme/templates/' ) !== false;
		$has_block_template_parts_dir = $zip->locateName( 'theme/parts/' ) !== false;
		$this->assertTrue( $has_theme_dir, 'theme directory exists' );
		$this->assertTrue( $has_block_templates_dir, 'theme/templates directory exists' );
		$this->assertTrue( $has_block_template_parts_dir, 'theme/parts directory exists' );

		// ZIP file contains at least one HTML file.
		$has_html_files = false;
		$num_files      = $zip->numFiles;
		for ( $i = 0; $i < $num_files; $i++ ) {
			$filename = $zip->getNameIndex( $i );
			if ( '.html' === substr( $filename, -5 ) ) {
				$has_html_files = true;
				break;
			}
		}
		$this->assertTrue( $has_html_files, 'contains at least one html file' );
	}
}
