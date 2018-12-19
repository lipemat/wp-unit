<?php

/**
 * Run all crons in cue during Testing
 *
 * @see wp/wp-cron.php
 *
 * @return void
 */
function wp_cron_run_all() {
	if ( false === $crons = _get_cron_array() ) {
		return;
	}

	$gmt_time = microtime( true );
	$keys     = array_keys( $crons );
	if ( isset( $keys[0] ) && $keys[0] > $gmt_time ) {
		return;
	}

	foreach ( $crons as $timestamp => $cronhooks ) {
		if ( $timestamp > $gmt_time ) {
			break;
		}
		foreach ( (array) $cronhooks as $hook => $keys ) {
			foreach ( $keys as $k => $v ) {
				$schedule = $v['schedule'];

				if ( false !== $schedule ) {
					$new_args = array( $timestamp, $schedule, $hook, $v['args'] );
					call_user_func_array('wp_reschedule_event', $new_args);
				}

				wp_unschedule_event( $timestamp, $hook, $v['args'] );
				do_action_ref_array( $hook, $v['args'] );
			}
		}
	}
}
