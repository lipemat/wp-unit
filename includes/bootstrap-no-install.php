<?php

/**
 * Really only sets a const so we can load the normal
 * bootstrap without actually installing WP or losing
 * data.
 *
 * @notice Even with this set it is still recommended to never
 *         have the wp-tests-config pointed to a production db.
 */

define( 'WP_TESTS_NO_INSTALL', true );

require __DIR__ . '/bootstrap.php';