<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Exceptions;

/**
 * A special exception class for the test helpers.
 *
 * - Allows us to know if an exception was specific to testing internals.
 * - Ignornable exception for PHPStorm.
 *
 * @since 4.8.0
 *
 */
class TestHelperException extends \Exception {

}
