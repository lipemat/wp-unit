<?php

if ( is_multisite() ) :

	/**
	 * A set of unit tests for WordPress Multisite
	 *
	 * @group multisite
	 */
	class Tests_Multisite extends WP_UnitTestCase {
		protected $suppress = false;

		function setUp() {
			global $wpdb;
			parent::setUp();
			$this->suppress = $wpdb->suppress_errors();
		}

		function tearDown() {
			global $wpdb;
			parent::tearDown();
			$wpdb->suppress_errors( $this->suppress );
		}

		function test_wpmu_log_new_registrations() {
			global $wpdb;

			$user = new WP_User( 1 );
			$ip   = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );

			wpmu_log_new_registrations( 1, 1 );

			// currently there is no wrapper function for the registration_log
			$reg_blog = $wpdb->get_col( $wpdb->prepare( "SELECT email FROM {$wpdb->registration_log} WHERE {$wpdb->registration_log}.blog_id = 1 AND IP LIKE %s", $ip ) );
			$this->assertEquals( $user->user_email, $reg_blog[ count( $reg_blog ) - 1 ] );
		}

		/**
		 * @ticket 37392
		 */
		function test_wp_count_sites() {
			// create a random number of sites with each status.
			$site_ids = array(
				'public'   => self::factory()->blog->create_many(
					random_int( 0, 5 ),
					array( 'meta' => array( 'public' => 1 ) )
				),
				'archived' => self::factory()->blog->create_many(
					random_int( 0, 5 ),
					array( 'meta' => array( 'archived' => 1 ) )
				),
				'mature'   => self::factory()->blog->create_many(
					random_int( 0, 5 ),
					array( 'meta' => array( 'mature' => 1 ) )
				),
				'spam'     => self::factory()->blog->create_many(
					random_int( 0, 5 ),
					array( 'meta' => array( 'spam' => 1 ) )
				),
				'deleted'  => self::factory()->blog->create_many(
					random_int( 0, 5 ),
					array( 'meta' => array( 'deleted' => 1 ) )
				),
			);

			$counts = wp_count_sites();

			$counts_by_status = array_map( 'count', $site_ids );
			$expected         = array_merge(
				array( 'all' => array_sum( $counts_by_status ) ),
				$counts_by_status
			);
			// add 1 to all & public for the main site.
			$expected['all']    += 1;
			$expected['public'] += 1;

			$this->assertEquals( $expected, $counts );
		}
	}

endif;
