<?php

/**
 * Basic abstract test class.
 *
 * All WordPress unit tests should inherit from this class.
 *
 * Functions as a proxy to allow for a project to extend the
 * `WP_UnitTestCase_Base` class for all test cases.
 *
 * To extend the `WP_UnitTestCase_Base` class:
 * 1. Create a `WP_UnitTestCase` class in your local project.
 * 2. Extend `WP_UnitTestCase_Base` in your class.
 * 3. Add your custom test methods to your class.
 * 4. Define a `WP_UNIT_TESTCASE_BASE` constant with the path to your class.
 */
abstract class WP_UnitTestCase extends WP_UnitTestCase_Base {
}
