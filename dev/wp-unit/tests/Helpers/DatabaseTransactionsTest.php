<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

/**
 * @author Mat Lipe
 * @since  July 2024
 *
 */
class DatabaseTransactionsTest extends \WP_UnitTestCase {
	private \wpdb $backup;

	public function set_up() {
		parent::set_up();

		$this->backup = clone $GLOBALS['wpdb'];

		$this->assertCount( 0, $GLOBALS['wpdb']->queries );
		$this->assertSame( 0, $GLOBALS['wpdb']->num_queries );
	}


	public function tear_down() {
		$GLOBALS['wpdb'] = $this->backup;
		parent::tear_down();
	}


	public function test_start_transaction(): void {
		global $wpdb;

		$this->assertCount( 0, $wpdb->queries );
		$this->assertSame( 0, $wpdb->num_queries );
		// Run some queries.
		$wpdb->query( 'SELECT 1;' );
		$this->assertGreaterThan( 0, \count( $wpdb->queries ) );
		$this->assertSame( \count( $wpdb->queries ), $wpdb->num_queries );

		$mock = $this->getMockBuilder( 'wpdb' )
		             ->disableOriginalConstructor()
		             ->onlyMethods( [ 'query' ] )
		             ->getMock();
		$mock->num_queries = $wpdb->num_queries;
		$mock->queries = $wpdb->queries;

		$mock->expects( $this->exactly( 3 ) )
		     ->method( 'query' )
			->with(
				$this->callback( function( $arg ) {
					static $calls = [
						'SELECT 1;',
						'SET autocommit = 0;',
						'START TRANSACTION;',
					];
					return in_array( $arg, $calls );
				} )
			);

		$wpdb = $mock;

		// Run some queries.
		$wpdb->query( 'SELECT 1;' );
		$this->assertGreaterThan( 0, \count( $wpdb->queries ) );
		$this->assertSame( \count( $wpdb->queries ), $wpdb->num_queries );

		$transactions = DatabaseTransactions::instance();
		$transactions->start_transaction();
		$this->assertSame( 0, $wpdb->num_queries );
		$this->assertSame( [], $wpdb->queries );
	}


	public function test_rollback_transaction(): void {
		global $wpdb;
		$this->assertCount( 0, $wpdb->queries );
		$this->assertSame( 0, $wpdb->num_queries );

		// Run some queries.
		$wpdb->query( 'SELECT 1;' );
		$this->assertGreaterThan( 0, \count( $wpdb->queries ) );
		$this->assertSame( \count( $wpdb->queries ), $wpdb->num_queries );

		$mock = $this->getMockBuilder( 'wpdb' )
		             ->disableOriginalConstructor()
		             ->getMock();
		$mock->num_queries = $wpdb->num_queries;
		$mock->queries = $wpdb->queries;

		$mock->expects( $this->once() )
		     ->method( 'query' )
		     ->with( 'ROLLBACK;' );

		$wpdb = $mock;

		$transactions = DatabaseTransactions::instance();
		$transactions->rollback_transaction();

		$this->assertSame( 1, $wpdb->num_queries );
		$this->assertSame( 'SELECT 1;', $wpdb->queries[0][0] );
	}


	public function test_create_temporary_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$mock = $this->getMockBuilder( 'wpdb' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$mock->method( 'tables' )
		     ->willReturn( [] );

		$mock->expects( $this->exactly( 3 ) )
		     ->method( 'query' )
		     ->with(
			     $this->callback( function( $arg ) {
				     static $i = 0;
				     $arg = \apply_filters( 'query', $arg );

				     static $calls = [
					     0 => 'CREATE TEMPORARY TABLE test_table ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY )',
					     1 => 'CREATE TABLE IF NOT EXISTS test_no_temp ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, constRAINT kk FOREIGN KEY (id) REFERENCES test_table(id) ON DELETE RESTRICT )',
					     2 => 'CREATE TABLE IF NOT EXISTS test_no_temp ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, constraint ky FOREIGN KEY (id) REFERENCES test_table(id) ON DELETE cascade )',
				     ];
				     $result = $arg === $calls[ $i ];
				     if ( $result ) {
					     $i ++;
				     }
				     return $result;
			     } )
		     );

		$wpdb = $mock;
		$wpdb->global_tables = [];

		\dbDelta( 'CREATE TABLE test_table ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY );' );
		\dbDelta( 'CREATE TABLE test_no_temp ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, constRAINT kk FOREIGN KEY (id) REFERENCES test_table(id) ON DELETE RESTRICT );' );
		\dbDelta( 'CREATE TABLE test_no_temp ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, constraint ky FOREIGN KEY (id) REFERENCES test_table(id) ON DELETE cascade );' );
	}
}
