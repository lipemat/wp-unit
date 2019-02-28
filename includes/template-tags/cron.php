<?php

global $wp_version;

/**
 * Run all crons in cue during Testing
 *
 * @see wp/wp-cron.php
 *
 * @return void
 */
function wp_cron_run_all() {
	foreach ( wp_get_ready_cron_jobs() as $timestamp => $cronhooks ) {
		foreach ( (array) $cronhooks as $hook => $keys ) {
			foreach ( $keys as $k => $v ) {
				$schedule = $v['schedule'];

				if ( false !== $schedule ) {
					$new_args = [ $timestamp, $schedule, $hook, $v['args'] ];
					call_user_func_array( 'wp_reschedule_event', $new_args );
				}

				wp_unschedule_event( $timestamp, $hook, $v['args'] );
				do_action_ref_array( $hook, $v['args'] );
			}
		}
	}
}

/**
 * Placeholder for the core function which is available after WP Core 5.1
 *
 * @todo someday can remove this in favor of just using the core.
 *
 */
if ( version_compare( $wp_version, '5.1', '<' ) ) {
	/**
	 * Retrieve cron jobs ready to be run.
	 *
	 * Returns the results of _get_cron_array() limited to events ready to be run,
	 * ie, with a timestamp in the past.
	 *
	 * @since 5.1.0
	 *
	 * @return array Cron jobs ready to be run.
	 */
	function wp_get_ready_cron_jobs() {
		/**
		 * Filter to preflight or hijack retrieving ready cron jobs.
		 *
		 * Returning an array will short-circuit the normal retrieval of ready
		 * cron jobs, causing the function to return the filtered value instead.
		 *
		 * @since 5.1.0
		 *
		 * @param null|array $pre Array of ready cron tasks to return instead. Default null
		 *                        to continue using results from _get_cron_array().
		 */
		$pre = apply_filters( 'pre_get_ready_cron_jobs', null );
		if ( null !== $pre ) {
			return $pre;
		}

		$crons = _get_cron_array();

		if ( false === $crons ) {
			return [];
		}

		$gmt_time = microtime( true );
		$keys     = array_keys( $crons );
		if ( isset( $keys[0] ) && $keys[0] > $gmt_time ) {
			return [];
		}

		$results = [];
		foreach ( $crons as $timestamp => $cronhooks ) {
			if ( $timestamp > $gmt_time ) {
				break;
			}
			$results[ $timestamp ] = $cronhooks;
		}

		return $results;
	}
}
