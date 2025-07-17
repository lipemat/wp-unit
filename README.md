# WP Unit

<p>
<a href="https://github.com/lipemat/wp-unitreleases"><img src="https://img.shields.io/packagist/v/lipemat/wp-unit.svg?label=version" alt=""/></a>
<img alt="" src="https://img.shields.io/badge/wordpress->=6.1.0-green.svg">
<img src="https://img.shields.io/packagist/php-v/lipemat/wp-unit.svg?color=brown" alt=""/>
<img alt="Packagist" src="https://img.shields.io/packagist/l/lipemat/wp-unit.svg">
</p>

Fork of WordPress core phpunit test suite to support bootstrapping an existing database plus many other enhancements.

> Note: Version 4 has diverged from the original WordPress core tests. [More information here.](https://github.com/lipemat/wp-unit/wiki/Version-4-Migration)

Original may be cloned from here: **git://develop.git.wordpress.org/tests/phpunit**

## Usage

Install using composer either in your project or in a global location to be used among all projects.

```bash
composer require --dev lipemat/wp-unit
```

Example phpunit.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- Version 1.2.0 -->
<phpunit backupGlobals="false"
         backupStaticAttributes="false"
         colors="true"
         convertDeprecationsToExceptions="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="true"
         bootstrap="bootstrap.php"
>
    <php>
        <env name="HTTP_HOST" value="starting-point.loc" />
    </php>
    <testsuites>
        <testsuite name="Starting Point">
            <directory>./tests</directory>
        </testsuite>
    </testsuites>
</phpunit>

```

Example bootstrap.php file

```php
<?php
require __DIR__ . '/wp-tests-config.php'
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/lipemat/wp-unit/includes/bootstrap.php';
```

Example wp-tests-config.php

```php
<?php
define( 'DB_NAME', 'tests' );
define( 'DB_USER', 'user' );
define( 'DB_PASSWORD', 'password' );
define( 'DB_HOST', 'localhost' );

define( 'ABSPATH', WP_TESTS_DIR . '/' );
define( 'DOMAIN_CURRENT_SITE', 'wp-libs.loc' );
define( 'WP_TESTS_CONFIG_FILE_PATH',  __FILE__ );
define( 'WP_PHP_BINARY', 'php' );
// Root of your site/
define( 'WP_TESTS_DIR', dirname( __DIR__ ) );
define( 'WP_TESTS_DOMAIN', 'tests.loc' );
define( 'WP_TESTS_DOMAIN', 'wp-libs.loc' );
define( 'WP_TESTS_EMAIL', 'unit-tests@test.com' );
define( 'WP_TESTS_TITLE', 'WordPress Unit Tests' );
define( 'WP_UNIT_DIR', __DIR__ . '/vendor/lipemat/wp-unit' );

// If using snapshot testing.
define( 'WP_TESTS_SNAPSHOTS_BASE', 'Lipe\Project' );
define( 'WP_TESTS_SNAPSHOTS_DIR', __DIR__ . '/__snapshots__' );

// If you're not bootstrapping an exiting database.
define( 'WP_TESTS_TABLE_PREFIX', 'tests_' );

// If your tests must use `https` URL.
define( 'WP_TESTS_SSL', true );
```

Example unit test /tests/ExampleTest.php

```php
<?php
class ExampleTest extends WP_UnitTestCase {
	public function test_examples() : void {
		$this->assertTrue( true );
		$this->assertFalse( false );
	}
}
```

Run the test suite like any other phpunit suite

```bash
phpunit
```

## Excluding PHPUnit From Composer

If you are using an external PHP Unit executable or .phar and do not want `phpunit/phpunit` to be installed as part of your composer vendor, you may add the following configuration to your `composer.json` file.

```json
{
  "replace": {
    "phpunit/phpunit": "*"
  }
}
```

## Enhancements

### Network Options

Setting a wp_tests_options value may also be used to set a network option.
Set test options like normal, and they will automatically replace network option values as well.
```php
<?php
$GLOBALS['wp_tests_options'][ 'site_name' ] = 'Example Site Name';
```

### Run all scheduled cron events.

Used for testing cron events by running them if they are scheduled to run.

```php
wp_cron_run_all()
```

### Local wp-tests-config.php

You may set up your wp-tests.config.php in the directory of your bootstrap.php and phpunit.xml. Really this can be put anywhere as long as you point to it in your bootstrap.php file.
```php
<?php
require __DIR__ . '/wp-tests-config.php';
```

### Bootstrap WP on existing database

Using the bootstrap-no-install.php allows you to test it against your current data in the database. Out of the box it supports MySQL transactions to allow tests to set and use data without actually storing it in the database.

1. Update your wp-tests-config.php file to point to a database you want to use.
2. Change your bootstrap.php to use the bootstrap-no-install.php file like so;
```php
<?php
require __DIR__ . '/wp-tests-config.php';
require __DIR__ . '/vendor/lipemat/wp-unit/includes/bootstrap-no-install.php';
```
**Gotchas:**
1. If you override the WP_UnitTestCase::setUp() method in your test class, be sure to call parent::setUp(). Otherwise, any data you set during tests will persist in the database.
   1. The test cases are designed to detect these kinds of issues, so if this is not setup correctly, you will see a warning in your test output.
2. If your database tables are using MyISAM storage engines, data will persist.
   1. They must be converted to InnoDB or any other engine that supports transactions.

### Global filters which apply to all tests

From within your wp-tests-config.php file, add some filters to the $GLOBALS[ 'wp_tests_filters' ]
```php
<?php
$GLOBALS[ 'wp_tests_filters' ][ 'the_title' ] = function ( $title ) {
	return 'Example Title';
};
```

### Turn on mail sending
```php
<?php
define( 'WP_TESTS_SEND_MAIL', true );
```

### Set a memory limit

From within your wp-tests.config.php add a custom memory limit.
```php
<?php
define( 'WP_MEMORY_LIMIT', '128M' );
```

### Use an external "languages" directory

From within your wp-tests.config.php add a custom language directory.
```php
<?php
define( 'WP_LANG_DIR', __DIR__ . '/languages' );
```

### Prevent plugins from breaking mysql transactions

Some third party plugins use their own transactions which cause unpredictable results with the transactions used by `wp-unit`.

This library automatically accounts for outside transactions.

### Support custom ajax methods.

Sometimes you want to use ajax responses to calls which live outside the `wp_ajax` actions.

This library adds methods to `WP_Ajax_UnitTestCase`:
1. `_handleAjaxCustom` which will turn any callable into an `wp_ajax` action then call it via `_handleAjax`.
2. `_getJsonResult` call any callable which uses `wp_send_json_success` or `wp_send_json_error` and return the result.

### Support raw request testing.

Sometimes you want to verify requests are actually going out and not just
assert that a method which sends requests is being called.

1. Extend the `WP_Http_Remote_Post_TestCase` from your test's class.
2. All requests will not be sent but instead be stored in the test's class' properties.
3. Retrieve sent via `$this->get_sent()`.
4. Mock raw responses via `$this->mock_response`.

### Support object cache testing

For testing your object cache, a helper TestCase is available.
Automatically resets for a fresh object cache between tests.

1. Extend the `Object_Cache_TestCase` from your test's class.
2. Interact with `$this->object_cache` to access your object cache.
3. Use the include helper assertions and utilities.
    1. `assertNotCacheExternal` - Assert a key is not available in the external cache.
    2. `assertCacheExternal` - Assert a key is available in the external cache.
    3. `assertCachePropertyAndExternal` - Assert a value is same in the runtime cache as external cache.
    4. `get_cache_key` - Get parsed key sent to external cache.

### Automatically generate files for Attachment factory.

Many follow-up attachment calls require the attachment to have an actual file attached to it.
For example `get_the_post_thumbnail_url` will always be empty if a file does not exist.

This automatically adds files to the `create` call via `self::factory()->attachment`.

```php
<?php
$post = self::factory()->post->create_and_get();
$attachment = self::factory()->attachment->create_and_get();
set_post_thumbnail( $post->ID, $attachment->ID );
// Will return something like `https:///wp-content/uploads//tmp/canola.jpg`
get_the_post_thumbnail_url( $post->ID );

```

### Support assertEqualSetsValues on all TestCases

Support testing two arrays of values while accounting for order but ignoring array keys.
Useful for testing things like pagination.

Example:
```php
$categories = \get_categories( [
			'orderby' => 'term_id',
			'order'   => 'ASC',
		] );
$per_page = 20;
$this->assertEqualSetsValues(
			wp_list_pluck( array_slice( $categories, $per_page * 4, $per_page ), 'term_id' ),
			wp_list_pluck( $this->get_results( 5 )->categories, 'id' )
		)

```

### Support assertEqualSetsIndex on all TestCases

Asserts that the keys of two arrays are equal, regardless of the contents, without accounting for the order of elements.

### Support mocking `final` classes.

Includes an utilities library to enable mocking of any final classes.

[db/bypass-finals](https://github.com/dg/bypass-finals#usage)

Enable for tests via.
```php
DG\BypassFinals::enable();
```

### Extending the WP_UnitTestCase

Some projects require additional functionality to be added to every test case. Most commonly this is required for rolling back transactions on custom database adapters.

To use your own version of the `WP_UnitTestCase`:
1. Create a `WP_UnitTestCase` class in your local project.
2. Extend `WP_UnitTestCase_Base` in your class.
3. Add your custom test methods to your class.
4. Define a `WP_UNIT_TESTCASE_BASE` constant with the path to your class.

#### Example for an SQLite3 database

In your project create a `WP_UnitTestCase.php` file.
```php
class WP_UnitTestCase extends WP_UnitTestCase_Base {
	// Normally live somewhere in your project.
	static SQLite3 $sqlite_db;

	protected function setUp(): void {
		parent::setUp();
		self::sqlite_db = new SQLite3( ':memory:' );
		self::sqlite_db->exec( 'BEGIN TRANSACTION' );
	}

	protected function tearDown(): void {
		self::sqlite_db->exec( 'ROLLBACK' );
		parent::tearDown();
	}
}
```
In your bootstrap.php file add the following line.
```php
const WP_UNIT_TESTCASE_BASE = __DIR__ . '/WP_UnitTestCase.php';
```
