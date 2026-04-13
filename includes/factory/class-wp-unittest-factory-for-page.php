<?php
declare( strict_types=1 );

/**
 * Unit test factory for pages.
 *
 * @author Mat Lipe
 * @since  4.9.0
 *
 */
class WP_UnitTest_Factory_For_Page extends WP_UnitTest_Factory_For_Post {
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = [
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'Page title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Page content %s' ),
			'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Page excerpt %s' ),
			'post_type'    => 'page',
		];
	}
}
