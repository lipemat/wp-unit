<?php
declare( strict_types=1 );

use Lipe\WP_Unit\Generators\Template_String;

class WP_UnitTest_Generator_Sequence implements Template_String {
	public static int $incr = - 1;
	public $next;
	public $template_string;

	public function __construct( $template_string = '%s', $start = null ) {
		if ( $start ) {
			$this->next = $start;
		} else {
			++self::$incr;
			$this->next = self::$incr;
		}
		$this->template_string = $template_string;
	}


	public function next(): string {
		$generated = \sprintf( $this->template_string, $this->next );
		++$this->next;
		return $generated;
	}

	/**
	 * Get the incrementor.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_incr(): int {
		return self::$incr;
	}

	/**
	 * Get the template string.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_template_string(): string {
		return $this->template_string;
	}
}
