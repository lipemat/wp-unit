<?php
/**
 * Misc project specific declarations for phpstan.
 *
 * Holds any classes, constants, or functions which are otherwise
 * not available via included files and directories.
 *
 * @link https://phpstan.org/user-guide/discovering-symbols
 *
 *  scanFiles:
 *  - dev/stubs/stubs.php
 */

namespace {

	const WP_TESTS_SNAPSHOTS_DIR = __DIR__ . '/snapshots';
}
