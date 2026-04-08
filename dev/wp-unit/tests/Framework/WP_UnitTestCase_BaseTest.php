<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Framework;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * @author Mat Lipe
 * @since  November 2025
 *
 */
class WP_UnitTestCase_BaseTest extends \WP_UnitTestCase {
	/**
	 * @dataProvider provideWhitespaceStrings
	 */
	public function test_asssertSameIgnoreLeadingWhitespace( $actual, $expected, bool $result ): void {
		if ( $result ) {
			$this->assertSameIgnoreLeadingWhitespace( $expected, $actual );
		} else {
			try {
				$this->assertSameIgnoreLeadingWhitespace( $expected, $actual );
			} catch ( ExpectationFailedException $e ) {
				$this->assertSame( 'Failed asserting that two strings are identical.', $e->getMessage() );
			}
		}
	}


	public function test_assertWaitFor(): void {
		$ran = 0;
		$this->assertWaitFor( function( $i ) use ( &$ran ) {
			$ran ++;
			if ( $i < 2 ) {
				$this->assertTrue( false === $i );
			}
			$this->assertFalse( false );
		}, 5, 100 );

		$this->assertSame( 3, $ran );
	}


	public static function provideWhitespaceStrings(): array {
		return [
			'no whitespace'             => [
				'actual'   => 'Hello World',
				'expected' => 'Hello World',
				'result'   => true,
			],
			'leading whitespace'        => [
				'actual'   => '   Hello World',
				'expected' => 'Hello World',
				'result'   => true,
			],
			'trailing whitespace'       => [
				'actual'   => 'Hello World   ',
				'expected' => 'Hello World   ',
				'result'   => true,
			],
			'both leading and trailing' => [
				'actual'   => "   Hello World   ",
				'expected' => 'Hello World   ',
				'result'   => true,
			],
			'different strings'         => [
				'actual'   => 'Hello World!',
				'expected' => 'Hello World',
				'result'   => false,
			],
			'only whitespace'           => [
				'actual'   => '     ',
				'expected' => '',
				'result'   => true,
			],
			'empty strings'             => [
				'actual'   => '',
				'expected' => '',
				'result'   => true,
			],
			'deep'                      => [
				'actual'   => [
					'foo' => '   bar   ',
				],
				'expected' => [
					'foo' => 'bar',
				],
				'result'   => true,
			],
			'html'                      => [
				'actual' => '        <html>
                    <p>Hello World</p> 
                    <a href="https://example.com">
                    Link
                    </a>
               </html>',

				'expected' => ' <html>
           <p>Hello World</p> 
      <a href="https://example.com">
                    Link
                    </a>
  </html>',
				'result'   => true,
			],
			'invalid html'              => [
				'actual' => '
   <html>
                    <p>Un-Hello World</p> 
                    <a href="https://example.com">
                    Link
                    </a>
               </html>',

				'expected' => ' <html>
           <p>Hello World</p> 
      <a href="https://example.com">
                    Link
                    </a>
  </html>',
				'result'   => false,
			],
			'tab at end' => [
				'actual'   => '<a href="https://plugins.matlipe.com/asm/category/gender/male/"><span class="advanced-sidebar-category-name">Custom Title</span> <span class="advanced-sidebar-category-count">(12)</span></a>
',
				'expected' => '<a href="https://plugins.matlipe.com/asm/category/gender/male/"><span class="advanced-sidebar-category-name">Custom Title</span> <span class="advanced-sidebar-category-count">(12)</span></a>',
				'result'   => true,
			],
		];
	}

}
