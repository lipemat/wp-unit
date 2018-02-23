# WP Unit
Fork of wp-unit to support bootstrapping an existing database and many other enhancements.

Original may be cloned from here: git://develop.git.wordpress.org/

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

**Site options**
Site options will automatically override the same as local blog options
```php
<?php
$GLOBALS['wp_tests_options'][ 'site_name' ] = 'Example Site Name';
```

**Local wp-tests-config.php**

You may setup your wp-tests.config.php in the directory of your bootstrap.php and phpunit.xml. Really this can be put anywhere as long as you point to it in your bootstrap.php file.
```php
<?php
require __DIR__ . '/wp-tests-config.php';
```


**Bootstrap WP on existing database** 

1. Update your wp-tests-config.php file to point to database you want to use.
2. Change your bootstrap.php to use the bootstrap-no-install.php file like so;
```php
<?php
require __DIR__ . '/wp-tests-config.php';
require __DIR__ . '/vendor/lipemat/wp-unit/includes/bootstrap-no-install.php';

```

**Set some filters before the tests load**

From within your wp-tests-config.php file add some filters to the $GLOBALS[ 'wp_tests_filters' ]
```php
<?php
$GLOBALS[ 'wp_tests_filters' ][ 'the_title' ] = function ( $title ) {
	return 'Example Title';
};
```

**Turn on mail sending**
```php
<?php
define( 'WP_TESTS_SEND_MAIL', true );
```

**Set a memory limit in your local wp-tests-config.php**
```php
<?php
define( 'WP_MEMORY_LIMIT', '128M' );
```

