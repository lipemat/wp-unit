<?php

declare( strict_types=1 );

namespace Lipe\WP_Unit\Helpers\Snapshots;

use DateTime;
use InvalidArgumentException;
use WP_UnitTestCase;

class MatcherTest extends WP_UnitTestCase {

	public function test_matches_snapshot(): void {
		$class = new class {
			private DateTime $date;

			private string $name;

			public int $id;

			public int $time;


			/**
			 * @noinspection UnusedConstructorDependenciesInspection
			 */
			public function __construct() {
				$this->date = new DateTime();
				$this->id = \random_int( 1, 1000 );
				$this->time = \time();
				$this->name = 'With Date Property';
			}
		};

		$matcher = new Matcher( $class, [
			'id'   => fn( $value ) => 99,
			'date' => function( \DateTime $value, $matcher ) {
				$this->assertSame( 'UTC', $value->getTimezone()->getName() );
				$this->assertGreaterThan( $matcher->time - 5, $value->getTimestamp() );
				return new DateTime( '2025-01-01 00:00:00' );
			},
			'time' => fn( $value ) => ( new DateTime( '2025-01-01 00:00:00' ) )->getTimestamp(),
		] );

		$this->assertMatchesFullSnapshot( $matcher );
	}


	public function test_get_snapshot_with_valid_array(): void {
		$data = [
			'key1' => 'value1',
			'key2' => 'value2',
		];

		$keys = [ 'key1', 'key2' ];
		$callbacks = [
			'key1' => fn( $value ) => strtoupper( $value ),
			'key2' => fn( $value ) => strtolower( $value ),
		];

		$matcher = new Matcher( $data, $callbacks );

		$expected = [
			'key1' => 'VALUE1',
			'key2' => 'value2',
		];

		$result = $matcher->get_snapshot();

		$this->assertSame( $expected, $result );
	}


	public function test_get_snapshot_with_valid_object(): void {
		$data = new class {
			public string $key1 = 'VALUE1';

			public string $key2 = 'VALUE2';
		};

		$keys = [ 'key1', 'key2' ];
		$callbacks = [
			'key1' => fn( $value ) => strtolower( $value ),
			'key2' => fn( $value ) => strtoupper( $value ),
		];

		$matcher = new Matcher( $data, $callbacks );

		$expected = new class {
			public string $key1 = 'value1';

			public string $key2 = 'VALUE2';
		};

		$result = $matcher->get_snapshot();

		$this->assertSame(
			[ 'key1' => $expected->key1, 'key2' => $expected->key2 ],
			[ 'key1' => $result->key1, 'key2' => $result->key2 ]
		);
	}


	public function test_get_snapshot_throws_exception_for_missing_key_in_array(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Key key3 does not exist in the array.' );

		$data = [ 'key1' => 'value1' ];
		$callbacks = [ 'key3' => fn( $value ) => $value ];

		$matcher = new Matcher( $data, $callbacks );
		$matcher->get_snapshot();
	}


	public function test_get_snapshot_throws_exception_for_missing_property_in_object(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Property `key3` does not exist.' );

		$data = new class {
			public string $key1 = 'value1';
		};

		$callbacks = [ 'key3' => fn( $value ) => $value ];

		$matcher = new Matcher( $data, $callbacks );
		$matcher->get_snapshot();
	}
}
