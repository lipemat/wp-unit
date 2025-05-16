<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Generators;

interface Template_String {
	/**
	 * Returns the template string.
	 *
	 * @return string The template string.
	 */
	public function get_template_string(): string;

}
