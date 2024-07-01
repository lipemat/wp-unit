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
	const WP_TESTS_DOMAIN = 'example.org';
	const WP_TESTS_EMAIL = 'you@me.com';
	const DIR_TEST_IMAGES = __DIR__ . '/data/images';
}
