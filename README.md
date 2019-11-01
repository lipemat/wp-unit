# WP Unit

<p>
<a href="https://github.com/lipemat/wp-unitreleases">
<img src="https://img.shields.io/packagist/v/lipemat/wp-unit.svg?label=version" />
</a>
    <img alt="" src="https://img.shields.io/badge/wordpress->=5.1.0-green.svg">
    <img src="https://img.shields.io/packagist/php-v/lipemat/wp-unit.svg?color=brown" />
    <img alt="Packagist" src="https://img.shields.io/packagist/l/lipemat/wp-unit.svg">
</p>

Fork of wp-unit to support bootstrapping an existing database and many other enhancements.

Original may be cloned from here: **git://develop.git.wordpress.org/tests/phpunit**

## Usage
Install via composer either in you project or in a global location to be used among all projects.

```bash
composer require --dev lipemat/wp-unit
```


Example phpunit.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>

<phpunit backupGlobals="false"
         backupStaticAttributes="false"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false"
         syntaxCheck="false"
         bootstrap="bootstrap.php"
        >
    <testsuites>
        <testsuite name="My Tests">
            <directory>./tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Example bootstrap.php file

```php
<?php
require __DIR__ . '/wp-tests-config.php';
require __DIR__ . '/vendor/lipemat/wp-unit/includes/bootstrap.php';
```

Example wp-tests-config.php

```php
<?php
define( 'DB_NAME', 'tests' );
define( 'DB_USER', 'user' );
define( 'DB_PASSWORD', 'password' );
define( 'DB_HOST', 'localhost' );

define( 'WP_TESTS_DOMAIN', 'tests.loc' );
//Root of your site
define( 'WP_TESTS_DIR', dirname( __DIR__ ) );
define( 'WP_UNIT_DIR', __DIR__ . '/vendor/lipemat/wp-unit' );

define( 'DOMAIN_CURRENT_SITE', 'starting-point.loc' );
```

Example unit test /tests/ExampleTest.php

```php
<?php
class ExampleTest extends WP_UnitTestCase {
	
	public function test_examples(){
		$this->assertTrue( true );
		$this->assertFalse( false );
	}
}

```

Run the test suite like any other phpunit suite

```bash
phpunit
```

## Enhancements

#### Network Options

Setting a wp_tests_options value may also be used to set a network option. 
Set test options like normal and they will automatically replace network option values as well.
```php
<?php
$GLOBALS['wp_tests_options'][ 'site_name' ] = 'Example Site Name';
```

#### Run all scheduled crons
Used for testing crons by running them if they are schedule to run.

```php
wp_cron_run_all()
```

#### Local wp-tests-config.php

You may setup your wp-tests.config.php in the directory of your bootstrap.php and phpunit.xml. Really this can be put anywhere as long as you point to it in your bootstrap.php file.
```php
<?php
require __DIR__ . '/wp-tests-config.php';
```


#### Bootstrap WP on existing database

Using the bootstrap-no-install.php allows you to test against your current data in the database. Out of the box it supports MySQL transactions to allow tests to set and use data without actually storing it in the database. 

1. Update your wp-tests-config.php file to point to database you want to use.
2. Change your bootstrap.php to use the bootstrap-no-install.php file like so;
```php
<?php
require __DIR__ . '/wp-tests-config.php';
require __DIR__ . '/vendor/lipemat/wp-unit/includes/bootstrap-no-install.php';
```
**Gotchas:**
1. If you override the WP_UnitTestCase::setUp() method in your test class, be sure to call parent::setUp(). Otherwise any data you set during tests will persist to the database.
2. If your database tables are using MyISAM storage engines, data will persist. They may be converted to InnoDB or any other engine which supports transactions. 


#### Global filters which apply to all tests

From within your wp-tests-config.php file add some filters to the $GLOBALS[ 'wp_tests_filters' ]
```php
<?php
$GLOBALS[ 'wp_tests_filters' ][ 'the_title' ] = function ( $title ) {
	return 'Example Title';
};
```

#### Turn on mail sending
```php
<?php
define( 'WP_TESTS_SEND_MAIL', true );
```

#### Set a memory limit 

From within your wp-tests.config.php add a custom memory limit.
```php
<?php
define( 'WP_MEMORY_LIMIT', '128M' );
```

#### Allow an outside languages directory
From within your wp-tests.config.php add a custom language directory.
```php
<?php
define( 'WP_LANG_DIR', __DIR__ . '/languages' );
```

#### Prevent plugins from breaking mysql transactions

Some third party plugins use their own transactions which cause unpredictable results with the transactions used by `wp-unit`.

This library automatically accounts for outside transactions. 

#### Support custom ajax methods.

Sometimes you want to use ajax responses to calls which live outside the `wp_ajax` actions.

This library adds a methods to `WP_Ajax_UnitTestCase`:
 1.  `_handleAjaxCustom` which will turn any callable into an `wp_ajax` action then call it via `_handleAjax`.
 2. `_getJsonResult` call any callable which uses `wp_send_json_success` or `wp_send_json_error` and return the result.


#### Automatically generate files for Attachment factory.

Many follow up attachment calls require the attachment to have an actual file attached to it.
For example `get_the_post_thumbnail_url` will always be empty if a file does not exist.

This automatically adds files to the `create` call via `self::factory()->attachment`.

```php
<?php
$post = self::factory()->post->create_and_get();
$attachment = self::factory()->attachment->create_and_get();
set_post_thumbnail( $post->ID, $attachment->ID );
// Will return something like `http:///wp-content/uploads//tmp/canola.jpg`
get_the_post_thumbnail_url( $post->ID );

```

