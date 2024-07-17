<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Utils;

use Lipe\WP_Unit\Traits\Singleton;
use PHPUnit\Metadata\Annotation\Parser\Registry;
use PHPUnit\Util\Test;

/**
 * Utilities for working on PHPDoc annotations.
 *
 * @author Mat Lipe
 * @since  3.7.0
 */
class Annotations {
	use Singleton;

	/**
	 * Cross PHPUnit version method to get annotations.
	 *
	 * @param \WP_UnitTestCase_Base $case
	 *
	 * @return array{
	 *     method: array<string, string[]>,
	 *     class: array<string, string[]>
	 * }
	 */
	public function get_annotations( \WP_UnitTestCase_Base $case ): array {
		if ( \method_exists( Test::class, 'parseTestMethodAnnotations' ) ) { // @phpstan-ignore-line
			// PHPUnit >= 9.5.0.
			$annotations = Test::parseTestMethodAnnotations(
				\get_class( $case ),

				$case->getName( false )

			);
		} else {
			// PHPUnit >= 10.5.0
			$annotations = [
				'method' => Registry::getInstance()->forMethod( \get_class( $case ), $case->name() )->symbolAnnotations(), // @phpstan-ignore-line
				'class'  => Registry::getInstance()->forClassName( static::class )->symbolAnnotations(), // @phpstan-ignore-line
			];
		}
		return $annotations;
	}
}
