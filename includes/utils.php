<?php
declare( strict_types=1 );

// Misc help functions and utilities.

/**
 * Returns a string of the required length containing random characters. Note that
 * the maximum possible string length is 32.
 *
 * @param int $length Optional. The required length. Default 32.
 * @return string The string.
 */
function tests_rand_str( int $length = 32 ): string {
	return \substr( \md5( \uniqid( (string) mt_rand(), true ) ), 0, $length );
}

/**
 * Returns a string of the required length containing random characters.
 *
 * @param int $length The required length.
 *
 * @return string The string.
 */
function tests_rand_long_str( int $length ): string {
	$chars  = 'abcdefghijklmnopqrstuvwxyz';
	$string = '';

	for ( $i = 0; $i < $length; $i++ ) {
		$rand = \random_int( 0, \strlen( $chars ) - 1 );
		$string .= $chars[ $rand ];
	}

	return $string;
}

/**
 * Strips leading and trailing whitespace from each line in the string.
 *
 * @param string $txt The text.
 *
 * @return string Text with line-leading and line-trailing whitespace stripped.
 */
function tests_strip_ws( string $txt ): string {
	$lines  = explode( "\n", $txt );
	$result = array();
	foreach ( $lines as $line ) {
		if ( '' !== \trim( $line ) ) {
			$result[] = trim( $line );
		}
	}

	return \trim( \implode( "\n", $result ) );
}

/**
 * Use to match HTML strings that have been formatted differently.
 *
 * - Strip tabs
 * - Strip new lines
 * - Strip multiple spaces
 *
 * @param string $html
 *
 * @return string
 */
function tests_strip_ws_all( string $html ): string {
	$html = \preg_replace_callback( '/<[^>]+>/', function( $matches ) {
		return \str_replace( "\n", ' ', $matches[0] );
	}, $html );
	// Remove new lines and tabs
	$html = (string) \preg_replace( '/[\n\t]/', '', $html );

	// Remove multiple spaces
	return \trim( \preg_replace( '/\s\s+/', ' ', $html ) );
}

// Convert valid XML to an array tree structure.
// Kinda lame, but it works with a default PHP 4 installation.
class TestXMLParser {
	public $xml;
	public $data = array();

	/**
	 * PHP5 constructor.
	 */
	public function __construct( $in ) {
		$this->xml = xml_parser_create();
		xml_parser_set_option( $this->xml, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_element_handler( $this->xml, array( $this, 'start_handler' ), array( $this, 'end_handler' ) );
		xml_set_character_data_handler( $this->xml, array( $this, 'data_handler' ) );
		$this->parse( $in );
	}

	public function parse( $in ) {
		$parse = xml_parse( $this->xml, $in, true );
		if ( ! $parse ) {
			trigger_error(
				sprintf(
					'XML error: %s at line %d',
					xml_error_string( xml_get_error_code( $this->xml ) ),
					xml_get_current_line_number( $this->xml )
				),
				E_USER_ERROR
			);
			xml_parser_free( $this->xml );
		}
		return true;
	}

	public function start_handler( $parser, $name, $attributes ) {
		$data['name'] = $name;
		if ( $attributes ) {
			$data['attributes'] = $attributes; }
		$this->data[] = $data;
	}

	public function data_handler( $parser, $data ) {
		$index = count( $this->data ) - 1;

		if ( ! isset( $this->data[ $index ]['content'] ) ) {
			$this->data[ $index ]['content'] = '';
		}
		$this->data[ $index ]['content'] .= $data;
	}

	public function end_handler( $parser, $name ) {
		if ( count( $this->data ) > 1 ) {
			$data                            = array_pop( $this->data );
			$index                           = count( $this->data ) - 1;
			$this->data[ $index ]['child'][] = $data;
		}
	}
}

/**
 * Converts an XML string into an array tree structure.
 *
 * The output of this function can be passed to tests_xml_find() to find nodes by their path.
 *
 * @param string $in The XML string.
 *
 * @return array XML as an array.
 */
function tests_xml_to_array( string $in ): array {
	$p = new TestXMLParser( $in );
	return $p->data;
}

/**
 * Finds XML nodes by a given "path".
 *
 * Example usage:
 *
 *     $tree = tests_xml_to_array( $rss );
 *     $items = tests_xml_find( $tree, 'rss', 'channel', 'item' );
 *
 * @param array $tree An array tree structure of XML, typically from tests_xml_to_array().
 * @param string ...$elements Names of XML nodes to create a "path" to find within the XML.
 *
 * @return array Array of matching XML node information.
 */
function tests_xml_find( array $tree, ...$elements ): array {
	$n   = count( $elements );
	$out = array();

	if ( $n < 1 ) {
		return $out;
	}

	for ( $i = 0, $iMax = count( $tree ); $i < $iMax; $i ++ ) {
		#       echo "checking '{$tree[$i][name]}' == '{$elements[0]}'\n";
		#       var_dump( $tree[$i]['name'], $elements[0] );
		if ( $tree[ $i ]['name'] === $elements[0] ) {
			#           echo "n == {$n}\n";
			if ( 1 === $n ) {
				$out[] = $tree[ $i ];
			} else {
				$subtree =& $tree[ $i ]['child'];
				$out = array_merge( $out, tests_xml_find( $subtree, ...array_slice( $elements, 1 ) ) );
			}
		}
	}

	return $out;
}

function tests_xml_join_attrs( iterable $attributes ): string {
	$a = array();
	foreach ( $attributes as $k => $v ) {
		$a[] = $k . '="' . $v . '"';
	}
	return \implode( ' ', $a );
}

/**
 * Flattens an array tree structure of XML into a single-dimensional array.
 */
function tests_xml_array_flatten( array $data ): array {
	$out = array();

	foreach ( \array_keys( $data ) as $i ) {
		$name = $data[ $i ]['name'];
		if ( isset( $data[ $i ]['attributes'] ) && \is_array( $data[ $i ]['attributes'] ) ) {
			$name .= ' ' . tests_xml_join_attrs( $data[ $i ]['attributes'] );
		}

		if ( isset( $data[ $i ]['child'] ) && \is_array( $data[ $i ]['child'] ) ) {
			$out[ $name ][] = tests_xml_array_flatten( $data[ $i ]['child'] );
		} else {
			$out[ $name ] = $data[ $i ]['content'];
		}
	}

	return $out;
}

function tests_dmp( ...$args ) {
	foreach ( $args as $thing ) {
		echo ( is_scalar( $thing ) ? (string) $thing : var_export( $thing, true ) ), "\n";
	}
}

function tests_dmp_filter( $a ) {
	tests_dmp( $a );
	return $a;
}

function tests_get_echo( $callback, $args = [] ): string {
	\ob_start();
	\call_user_func_array( $callback, $args );
	return (string) \ob_get_clean();
}
/**
 * Drops all tables from the WordPress database.
 */
function tests_drop_tables() {
	global $wpdb;
	$tables = $wpdb->get_col( 'SHOW TABLES;' );
	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}

function tests_print_backtrace() {
	$bt = debug_backtrace();
	echo "Backtrace:\n";
	$i = 0;
	foreach ( $bt as $stack ) {
		echo ++$i, ': ';
		if ( isset( $stack['class'] ) ) {
			echo $stack['class'] . '::';
		}
		if ( isset( $stack['function'] ) ) {
			echo $stack['function'] . '() ';
		}
		echo "line {$stack[line]} in {$stack[file]}\n";
	}
	echo "\n";
}

function tests_mask_input_value( $in, $name = '_wpnonce' ) {
	return preg_replace( '@<input([^>]*) name="' . preg_quote( $name ) . '"([^>]*) value="[^>]*" />@', '<input$1 name="' . preg_quote( $name ) . '"$2 value="***" />', $in );
}

/**
 * Removes the post type and its taxonomy associations.
 */
function tests_unregister_post_type( $cpt_name ) {
	unregister_post_type( $cpt_name );
}

function tests_unregister_taxonomy( $taxonomy_name ) {
	unregister_taxonomy( $taxonomy_name );
}

/**
 * Unregister a post stat
 * us.
 *
 * @since 4.2.0
 *
 * @param string $status
 */
function tests_unregister_post_status( $status ) {
	unset( $GLOBALS['wp_post_statuses'][ $status ] );
}

function tests_cleanup_query_vars() {
	// Clean out globals to stop them polluting wp and wp_query.
	foreach ( $GLOBALS['wp']->public_query_vars as $v ) {
		unset( $GLOBALS[ $v ] );
	}

	foreach ( $GLOBALS['wp']->private_query_vars as $v ) {
		unset( $GLOBALS[ $v ] );
	}

	foreach ( get_taxonomies( array(), 'objects' ) as $t ) {
		if ( $t->publicly_queryable && ! empty( $t->query_var ) ) {
			$GLOBALS['wp']->add_query_var( $t->query_var );
		}
	}

	foreach ( get_post_types( array(), 'objects' ) as $t ) {
		if ( is_post_type_viewable( $t ) && ! empty( $t->query_var ) ) {
			$GLOBALS['wp']->add_query_var( $t->query_var );
		}
	}
}

function tests_clean_term_filters() {
	remove_filter( 'get_terms', array( 'Featured_Content', 'hide_featured_term' ), 10, 2 );
	remove_filter( 'get_the_terms', array( 'Featured_Content', 'hide_the_featured_term' ), 10, 3 );
}

/**
 * Determine approximate backtrack count when running PCRE.
 *
 * @return int The backtrack count.
 */
function tests_benchmark_pcre_backtracking( $pattern, $subject, $strategy ) {
	$saved_config = ini_get( 'pcre.backtrack_limit' );

	// Attempt to prevent PHP crashes. Adjust lower when needed.
	$limit = 1000000;

	// Start with small numbers, so if a crash is encountered at higher numbers we can still debug the problem.
	for ( $i = 4; $i <= $limit; $i *= 2 ) {

		ini_set( 'pcre.backtrack_limit', $i );

		switch ( $strategy ) {
			case 'split':
				preg_split( $pattern, $subject );
				break;
			case 'match':
				preg_match( $pattern, $subject );
				break;
			case 'match_all':
				$matches = array();
				preg_match_all( $pattern, $subject, $matches );
				break;
		}

		ini_set( 'pcre.backtrack_limit', $saved_config );

		switch ( preg_last_error() ) {
			case PREG_NO_ERROR:
				return $i;
			case PREG_BACKTRACK_LIMIT_ERROR:
				break;
			case PREG_RECURSION_LIMIT_ERROR:
				trigger_error( 'PCRE recursion limit encountered before backtrack limit.' );
				return;
			case PREG_BAD_UTF8_ERROR:
				trigger_error( 'UTF-8 error during PCRE benchmark.' );
				return;
			case PREG_INTERNAL_ERROR:
				trigger_error( 'Internal error during PCRE benchmark.' );
				return;
			default:
				trigger_error( 'Unexpected error during PCRE benchmark.' );
				return;
		}
	}

	return $i;
}

function test_rest_expand_compact_links( $links ) {
	if ( empty( $links['curies'] ) ) {
		return $links;
	}
	foreach ( $links as $rel => $links_array ) {
		if ( ! strpos( $rel, ':' ) ) {
			continue;
		}

		$name = explode( ':', $rel );

		$curie              = wp_list_filter( $links['curies'], array( 'name' => $name[0] ) );
		$full_uri           = str_replace( '{rel}', $name[1], $curie[0]['href'] );
		$links[ $full_uri ] = $links_array;
		unset( $links[ $rel ] );
	}
	return $links;
}
