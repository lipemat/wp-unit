<?php
/**
 * Run all crons in queue during Testing
 *
 * @see wp/wp-cron.php
 * @see wp_cron_isolate_events()
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

				do_action_ref_array( $hook, $v['args'] );
				wp_unschedule_event( $timestamp, $hook, $v['args'] );
				if ( isset( $v['schedule'] ) ) {
					wp_reschedule_event( $timestamp, $v['schedule'], $hook, $v['args']);
				}
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
	foreach ( _get_cron_array() as $timestamp => $crooks ) {
		foreach ( (array) $crooks as $_hook => $keys ) {
			if ( $hook !== $_hook ) {
				continue;
			}
			foreach ( $keys as $v ) {
				do_action_ref_array( $hook, $v['args'] );
				wp_unschedule_event( $timestamp, $_hook, $v['args'] );
				if ( isset( $v['schedule'] ) ) {
					wp_reschedule_event( $timestamp, $v['schedule'], $_hook, $v['args'] );
				}
			}
		}
	}
}

/**
 * Limit the cron events to actions which include a specific selector.
 *
 * Used to prevent a bunch of core and third party crons from running when
 * using `wp_cron_run_all`.
 *
 * @since 3.5.0
 *
 * @notice Must be used after `setUp` is called to prevent affecting the database.
 *
 * @example wp_cron_isolate_events( 'lipe' );
 *
 *
 * @param string $selector - Selector to limit cron events to.
 *                           Will match any cron event which includes this string.
 *
 * @return void
 */
function wp_cron_isolate_events( $selector ) {
	update_option( 'cron', \array_filter( \array_map( function( $timed_events ) use ( $selector ) {
		if ( ! is_array( $timed_events ) ) {
			return $timed_events;
		}
		return \array_filter( $timed_events, function( $action ) use ( $selector ) {
			return false !== \strpos( $action, $selector );
		}, ARRAY_FILTER_USE_KEY );
	}, get_option( 'cron', [] ) ) ) );
}
