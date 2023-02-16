<?php
/**
 * Run all crons in queue during Testing
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
 * Run a single cron event regardless of ready state.
 *
 * @param string $hook - Hook, which was registered with the event.
 *
 * @return void
 */
function wp_cron_run_event( $hook ) {
	foreach ( _get_cron_array() as $crooks ) {
		foreach ( (array) $crooks as $_hook => $keys ) {
			if ( $hook !== $_hook ){
				continue;
			}
			foreach ( $keys as $v ) {
				do_action_ref_array( $hook, $v['args'] );
			}
		}
	}
}
