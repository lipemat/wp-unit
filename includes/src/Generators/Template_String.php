<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

/**
 * A generator which returns a template string for an object field
 * which may contain a `%s` placeholder to be replaced with the
 * unique incrementing object count.
 *
 * Called before the object is created to generate the initial
 * creation value for the object field.
 */
interface Template_String {
	/**
	 * Returns the template string.
	 *
	 * @return string The template string.
	 */
	public function get_template_string(): string;

}
