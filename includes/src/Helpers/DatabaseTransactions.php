<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers;

use Lipe\WP_Unit\Traits\Singleton;

/**
 * @author Mat Lipe
 * @since  4.0.0
 *
 */
class DatabaseTransactions {
	use Singleton;

	/**
	 * Starts a database transaction.
	 */
	public function start_transaction(): void {
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'START TRANSACTION;' );
		add_filter( 'query', [ $this, '_create_temporary_tables' ] );
		add_filter( 'query', [ $this, '_drop_temporary_tables' ] );
		add_filter( 'query', [ $this, '_prevent_premature_commit' ] );
		add_filter( 'query', [ $this, '_prevent_second_transaction' ] );

		$wpdb->num_queries = 0;
		$wpdb->queries = [];
	}


	/**
	 * Cleanup the transaction.
	 *
	 * @return void
	 */
	public function rollback_transaction(): void {
		global $wpdb;
		remove_filter( 'query', [ $this, '_create_temporary_tables' ] );
		remove_filter( 'query', [ $this, '_drop_temporary_tables' ] );

		$wpdb->query( 'ROLLBACK;' );
	}


	/**
	 * Commits the queries in a transaction.
	 *
	 * @since 4.1.0
	 */
	public function commit_transaction(): void {
		global $wpdb;
		remove_filter( 'query', [ $this, '_prevent_premature_commit' ] );
		$wpdb->query( 'COMMIT;' );
		add_filter( 'query', [ $this, '_prevent_premature_commit' ] );
	}


	/**
	 * Replaces the `CREATE TABLE` statement with a `CREATE TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 *
	 * @return string The altered query.
	 */
	public function _create_temporary_tables( string $query ): string {
		if ( 0 === stripos( \trim( $query ), 'CREATE TABLE' ) ) {
			// Temporary tables cannot have constraints.
			if ( false !== \strpos( \strtolower( $query ), 'constraint' ) ) {
				if ( false === \strpos( \strtolower( $query ), 'if not exists' ) ) {
					return \substr_replace( \trim( $query ), 'CREATE TABLE IF NOT EXISTS', 0, 12 );
				}
				return $query;
			}
			return \substr_replace( \trim( $query ), 'CREATE TEMPORARY TABLE', 0, 12 );
		}
		return $query;
	}


	/**
	 * Replaces the `DROP TABLE` statement with a `DROP TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 *
	 * @return string The altered query.
	 */
	public function _drop_temporary_tables( string $query ): string {
		if ( 0 === stripos( \trim( $query ), 'DROP TABLE' ) ) {
			return \substr_replace( \trim( $query ), 'DROP TEMPORARY TABLE', 0, 10 );
		}
		return $query;
	}


	public function _prevent_premature_commit( string $query ): string {
		if ( 0 === \stripos( \trim( $query ), 'COMMIT' ) ) {
			return 'SELECT "Bypassed COMMIT transaction _prevent_premature_commit"';
		}
		return $query;
	}


	public function _prevent_second_transaction( string $query ): string {
		if ( 0 === \stripos( \trim( $query ), 'START TRANSACTION' ) ) {
			return 'SELECT "Bypassed START TRANSACTIONT transaction _prevent_second_transaction"';
		}
		return $query;
	}
}
