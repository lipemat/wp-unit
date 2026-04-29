<?php
/**
 * @todo Remove this file in version 5
 *       - Add to migration wiki.
 */

if ( ! \function_exists( 'allow_extending_final' ) ) {
	/**
	 * @deprecated Use `tests_allow_extending_final()` instead.
	 */
	function allow_extending_final( string $class ): void {
		tests_allow_extending_final( $class );
	}
}

/**
 * @deprecated Use `tests_rand_str()` instead.
 */
function rand_str( $length = 32 ) {
	return tests_rand_str( $length );
}

/**
 * @deprecated Use `tests_rand_long_str()` instead.
 */
function rand_long_str( $length ) {
	return tests_rand_long_str( $length );
}

/**
 * @deprecated Use `tests_strip_ws()` instead.
 */
function strip_ws( $txt ) {
	return tests_strip_ws( $txt );
}

/**
 * @deprecated Use `tests_strip_ws_all()` instead.
 */
function strip_ws_all( string $html ): string {
	return tests_strip_ws_all( $html );
}

/**
 * @deprecated Use `tests_xml_to_array()` instead.
 */
function xml_to_array( $in ) {
	return tests_xml_to_array( $in );
}

/**
 * @deprecated Use `tests_xml_find()` instead.
 */
function xml_find( $tree, ...$elements ) {
	return tests_xml_find( $tree, ...$elements );
}

/**
 * @deprecated Use `tests_xml_join_atts()` instead.
 */
function xml_join_atts( $atts ) {
	return tests_xml_join_attrs( $atts );
}

/**
 * @deprecated Use `tests_xml_array_flatten()` instead.
 */
function xml_array_dumbdown( &$data ) {
	return tests_xml_array_flatten( $data );
}

/**
 * @deprecated Use `tests_dmp()` instead.
 */
function dmp( ...$args ) {
	tests_dmp( ...$args );
}

/**
 * @deprecated Use `tests_dmp_filter()` instead.
 */
function dmp_filter( $a ) {
	return tests_dmp_filter( $a );
}

/**
 * @deprecated Use `tests_get_echo()` instead.
 */
function get_echo( $callback, $args = [] ) {
	return tests_get_echo( $callback, $args );
}

/**
 * @deprecated Will be removed in version 5.
 */
function gen_tests_array( $name, $expected_data ) {
	$out = [];

	foreach ( $expected_data as $k => $v ) {
		if ( is_numeric( $k ) ) {
			$index = (string) $k;
		} else {
			$index = "'" . addcslashes( $k, "\n\r\t'\\" ) . "'";
		}

		if ( is_string( $v ) ) {
			$out[] = '$this->assertEquals( \'' . addcslashes( $v, "\n\r\t'\\" ) . '\', $' . $name . '[' . $index . '] );';
		} elseif ( is_numeric( $v ) ) {
			$out[] = '$this->assertEquals( ' . $v . ', $' . $name . '[' . $index . '] );';
		} elseif ( is_array( $v ) ) {
			$out[] = gen_tests_array( "{$name}[{$index}]", $v );
		}
	}

	return implode( "\n", $out ) . "\n";
}

/**
 * @deprecated Use `tests_drop_tables()` instead.
 */
function drop_tables() {
	tests_drop_tables();
}

/**
 * @deprecated Use `tests_print_backtrace()` instead.
 */
function print_backtrace() {
	tests_print_backtrace();
}

/**
 * @deprecated Use `tests_mask_input_value()` instead.
 */
function mask_input_value( $in, $name = '_wpnonce' ) {
	return tests_mask_input_value( $in, $name );
}

/**
 * @deprecated Use `tests__unregister_post_type()` instead.
 */
function _unregister_post_type( $cpt_name ) {
	tests__unregister_post_type( $cpt_name );
}

/**
 * @deprecated Use `tests__unregister_taxonomy()` instead.
 */
function _unregister_taxonomy( $taxonomy_name ) {
	tests_unregister_taxonomy( $taxonomy_name );
}

/**
 * @deprecated Use `tests__unregister_post_status()` instead.
 */
function _unregister_post_status( $status ) {
	tests_unregister_post_status( $status );
}

/**
 * @deprecated Use `tests__cleanup_query_vars()` instead.
 */
function _cleanup_query_vars() {
	tests_cleanup_query_vars();
}

/**
 * @deprecated Use `tests__clean_term_filters()` instead.
 */
function _clean_term_filters() {
	tests_clean_term_filters();
}

/**
 * @deprecated Use `tests_benchmark_pcre_backtracking()` instead.
 */
function benchmark_pcre_backtracking( $pattern, $subject, $strategy ) {
	return tests_benchmark_pcre_backtracking( $pattern, $subject, $strategy );
}

/**
 * @deprecated Will be removed in version 5.
 */
class MockClass extends stdClass {
}

/**
 * @deprecated Will be removed in version 5.
 */
class WpdbExposedMethodsForTesting extends wpdb {
	public function __construct() {
		global $wpdb;
		$this->dbh = $wpdb->dbh;
		$this->is_mysql = $wpdb->is_mysql;
		$this->ready = true;
		$this->field_types = $wpdb->field_types;
		$this->charset = $wpdb->charset;

		$this->dbuser = $wpdb->dbuser;
		$this->dbpassword = $wpdb->dbpassword;
		$this->dbname = $wpdb->dbname;
		$this->dbhost = $wpdb->dbhost;
	}


	public function __call( $name, $arguments ) {
		return call_user_func_array( [ $this, $name ], $arguments );
	}
}

/**
 * @deprecated Will be removed in version 5.
 */
class MockAction {
	public $events;

	public $debug;


	/**
	 * PHP5 constructor.
	 *
	 * @since UT (3.7.0)
	 */
	public function __construct( $debug = 0 ) {
		$this->reset();
		$this->debug = $debug;
	}


	/**
	 * @since UT (3.7.0)
	 */
	public function reset() {
		$this->events = [];
	}


	/**
	 * @since UT (3.7.0)
	 */
	public function current_filter() {
		global $wp_actions;

		if ( is_callable( 'current_filter' ) ) {
			return current_filter();
		}

		return end( $wp_actions );
	}


	/**
	 * @since UT (3.7.0)
	 */
	public function action( $arg ) {
		$current_filter = $this->current_filter();

		if ( $this->debug ) {
			tests_dmp( __FUNCTION__, $current_filter );
		}

		$this->events[] = [
			'action'    => __FUNCTION__,
			'hook_name' => $current_filter,
			'tag'       => $current_filter, // Back compat.
			'args'      => func_get_args(),
		];

		return $arg;
	}


	/**
	 * @since UT (3.7.0)
	 */
	public function action2( $arg ) {
		$current_filter = $this->current_filter();

		if ( $this->debug ) {
			tests_dmp( __FUNCTION__, $current_filter );
		}

		$this->events[] = [
			'action'    => __FUNCTION__,
			'hook_name' => $current_filter,
			'tag'       => $current_filter, // Back compat.
			'args'      => func_get_args(),
		];

		return $arg;
	}


	/**
	 * @since UT (3.7.0)
	 */
	public function filter( $arg ) {
		$current_filter = $this->current_filter();

		if ( $this->debug ) {
			tests_dmp( __FUNCTION__, $current_filter );
		}

		$this->events[] = [
			'filter'    => __FUNCTION__,
			'hook_name' => $current_filter,
			'tag'       => $current_filter, // Back compat.
			'args'      => func_get_args(),
		];

		return $arg;
	}


	/**
	 * @since UT (3.7.0)
	 */
	public function filter2( $arg ) {
		$current_filter = $this->current_filter();

		if ( $this->debug ) {
			tests_dmp( __FUNCTION__, $current_filter );
		}

		$this->events[] = [
			'filter'    => __FUNCTION__,
			'hook_name' => $current_filter,
			'tag'       => $current_filter, // Back compat.
			'args'      => func_get_args(),
		];

		return $arg;
	}


	/**
	 * @since UT (3.7.0)
	 */
	public function filter_append( $arg ) {
		$current_filter = $this->current_filter();

		if ( $this->debug ) {
			tests_dmp( __FUNCTION__, $current_filter );
		}

		$this->events[] = [
			'filter'    => __FUNCTION__,
			'hook_name' => $current_filter,
			'tag'       => $current_filter, // Back compat.
			'args'      => func_get_args(),
		];

		return $arg . '_append';
	}


	/**
	 * Does not return the result, so it's safe to use with the 'all' filter.
	 *
	 * @since UT (3.7.0)
	 */
	public function filterall( $hook_name, ...$args ) {
		$current_filter = $this->current_filter();

		if ( $this->debug ) {
			tests_dmp( __FUNCTION__, $current_filter );
		}

		$this->events[] = [
			'filter'    => __FUNCTION__,
			'hook_name' => $hook_name,
			'tag'       => $hook_name, // Back compat.
			'args'      => $args,
		];
	}


	/**
	 * Returns a list of all the actions, hook names and args.
	 *
	 * @since UT (3.7.0)
	 */
	public function get_events() {
		return $this->events;
	}


	/**
	 * Returns a count of the number of times the action was called since the last reset.
	 *
	 * @since UT (3.7.0)
	 */
	public function get_call_count( $hook_name = '' ) {
		if ( $hook_name ) {
			$count = 0;

			foreach ( $this->events as $e ) {
				if ( $e['action'] === $hook_name ) {
					++ $count;
				}
			}

			return $count;
		}

		return count( $this->events );
	}


	/**
	 * Returns an array of the hook names that triggered calls to this action.
	 *
	 * @since 6.1.0
	 */
	public function get_hook_names() {
		$out = [];

		foreach ( $this->events as $e ) {
			$out[] = $e['hook_name'];
		}

		return $out;
	}


	/**
	 * Returns an array of the hook names that triggered calls to this action.
	 *
	 * @since UT (3.7.0)
	 * @since 6.1.0 Turned into an alias for ::get_hook_names().
	 */
	public function get_tags() {
		return $this->get_hook_names();
	}


	/**
	 * Returns an array of args passed in calls to this action.
	 *
	 * @since UT (3.7.0)
	 */
	public function get_args() {
		$out = [];

		foreach ( $this->events as $e ) {
			$out[] = $e['args'];
		}

		return $out;
	}
}
