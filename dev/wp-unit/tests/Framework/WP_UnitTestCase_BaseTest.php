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
			$this->assertSameTrimWhitespace( $expected, $actual );
		} else {
			try {
				$this->assertSameTrimWhitespace( $expected, $actual );
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


	/**
	 * @dataProvider providerContainedTrimWhitespace
	 */
	public function test_assert_string_contains_string_trim_whitespace_passes( string $needle, string $haystack, string $message ): void {
		$this->assertStringContainsStringTrimWhitespace( $needle, $haystack, $message );
	}


	/**
	 * @dataProvider providerNotContainedTrimWhitespace
	 */
	public function test_assert_string_contains_string_trim_whitespace_fails( string $needle, string $haystack, string $message ): void {
		$this->expectException( ExpectationFailedException::class );
		$this->expectExceptionMessage( $message );

		$this->assertStringContainsStringTrimWhitespace( $needle, $haystack, $message );
	}


	public function test_assert_string_contains_string_trim_whitespace_failure_reports_trimmed_values(): void {
		$this->expectException( ExpectationFailedException::class );
		$this->expectExceptionMessageMatches( '#^Failed asserting that \'<p>Hello</p><p>World</p>\'.* contains "<p>Missing</p>"#' );

		$this->assertStringContainsStringTrimWhitespace( "  <p>Missing</p>\n", "\t<p>Hello</p>\n\t<p>World</p>\n" );
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


	/**
	 * @return array<string, array{needle: string, haystack: string, message: string}>
	 */
	public static function providerContainedTrimWhitespace(): array {
		return [
			'exact substring'          => [
				'needle'   => 'World',
				'haystack' => 'Hello World!',
				'message'  => 'A plain substring should be found.',
			],
			'identical strings'        => [
				'needle'   => 'Hello World',
				'haystack' => 'Hello World',
				'message'  => 'A string should contain itself.',
			],
			'empty needle'             => [
				'needle'   => '',
				'haystack' => 'Hello World',
				'message'  => 'An empty needle should always be found.',
			],
			'whitespace only needle'   => [
				'needle'   => " \t\n ",
				'haystack' => 'Hello World',
				'message'  => 'A whitespace-only needle trims to empty and should be found.',
			],
			'padded needle'            => [
				'needle'   => "   <p>Hello</p>\t\n",
				'haystack' => '<div><p>Hello</p></div>',
				'message'  => 'Leading and trailing whitespace on the needle should be ignored.',
			],
			'different indentation'    => [
				'needle'   => "<ul>\n  <li>One</li>\n  <li>Two</li>\n</ul>",
				'haystack' => "<div>\n\t\t<ul>\n\t\t\t\t<li>One</li>\n\t\t\t\t<li>Two</li>\n\t\t</ul>\n</div>",
				'message'  => 'Per-line indentation differences should be ignored.',
			],
			'crlf haystack'            => [
				'needle'   => "<li>One</li>\n<li>Two</li>",
				'haystack' => "<ul>\r\n  <li>One</li>\r\n  <li>Two</li>\r\n</ul>",
				'message'  => 'CRLF line endings in the haystack should match LF in the needle.',
			],
			'crlf needle'              => [
				'needle'   => "<li>One</li>\r\n<li>Two</li>",
				'haystack' => "<ul>\n  <li>One</li>\n  <li>Two</li>\n</ul>",
				'message'  => 'CRLF line endings in the needle should match LF in the haystack.',
			],
			'single line vs multiline' => [
				'needle'   => '<li>One</li><li>Two</li>',
				'haystack' => "<ul>\n  <li>One</li>\n  <li>Two</li>\n</ul>",
				'message'  => 'Line breaks between tags should be ignored.',
			],
			'trailing newline'         => [
				'needle'   => "<a href=\"https://example.com\">Link</a>\n",
				'haystack' => "<p><a href=\"https://example.com\">Link</a></p>",
				'message'  => 'A trailing newline on the needle should be ignored.',
			],
			'unicode'                  => [
				'needle'   => "  Héllo 🌍\n  Wörld",
				'haystack' => "<p>\n\tHéllo 🌍\n\tWörld\n</p>",
				'message'  => 'Multibyte characters should be preserved and matched.',
			],
		];
	}


	/**
	 * @return array<string, array{needle: string, haystack: string, message: string}>
	 */
	public static function providerNotContainedTrimWhitespace(): array {
		return [
			'missing text'                => [
				'needle'   => 'Goodbye',
				'haystack' => 'Hello World',
				'message'  => 'Text not in the haystack should fail.',
			],
			'case differs'                => [
				'needle'   => 'hello world',
				'haystack' => 'Hello World',
				'message'  => 'Matching should remain case-sensitive.',
			],
			'inner whitespace differs'    => [
				'needle'   => 'Hello  World',
				'haystack' => 'Hello World',
				'message'  => 'Whitespace inside a line should not be ignored.',
			],
			'empty haystack'              => [
				'needle'   => 'Hello',
				'haystack' => '',
				'message'  => 'A non-empty needle should not be found in an empty haystack.',
			],
			'whitespace only haystack'    => [
				'needle'   => 'Hello',
				'haystack' => "  \n\t\n  ",
				'message'  => 'A non-empty needle should not be found in a whitespace-only haystack.',
			],
			'line order differs'          => [
				'needle'   => "<li>Two</li>\n<li>One</li>",
				'haystack' => "<ul>\n  <li>One</li>\n  <li>Two</li>\n</ul>",
				'message'  => 'Lines in a different order should fail.',
			],
			'changed line in multiline'   => [
				'needle'   => "<li>One</li>\n<li>Three</li>",
				'haystack' => "<ul>\n  <li>One</li>\n  <li>Two</li>\n</ul>",
				'message'  => 'A changed line inside a multi-line needle should fail.',
			],
			'needle longer than haystack' => [
				'needle'   => '<p>Hello World</p>',
				'haystack' => 'Hello World',
				'message'  => 'A needle wrapping the haystack should fail.',
			],
		];
	}
}
