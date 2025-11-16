<?php
declare( strict_types=1 );

namespace Lipe\WP_Unit\Utils;

use Lipe\WP_Unit\Exceptions\TestHelperException;

/**
 * @author Mat Lipe
 * @since  November 2025
 *
 */
class PrivateAccessTest extends \WP_UnitTestCase {

	public function test_get_private_instance_property(): void {
		$fixture = new PrivateAccessTestFixture();
		$value = PrivateAccess::in()->get_private_property( $fixture, 'privateProperty' );
		$this->assertSame( 'private_value', $value );
	}


	public function test_get_protected_instance_property(): void {
		$fixture = new PrivateAccessTestFixture();
		$value = PrivateAccess::in()->get_private_property( $fixture, 'protectedProperty' );
		$this->assertSame( 'protected_value', $value );
	}


	public function test_get_private_static_property(): void {
		$value = PrivateAccess::in()->get_private_property( PrivateAccessTestFixture::class, 'privateStaticProperty' );
		$this->assertSame( 'static_private_value', $value );
	}


	public function test_get_private_static_property_from_instance(): void {
		$fixture = new PrivateAccessTestFixture();
		$value = PrivateAccess::in()->get_private_property( $fixture, 'privateStaticProperty' );
		$this->assertSame( 'static_private_value', $value );
	}


	public function test_get_private_constant(): void {
		$value = PrivateAccess::in()->get_private_property( PrivateAccessTestFixture::class, 'PRIVATE_CONSTANT' );
		$this->assertSame( 'constant_value', $value );
	}


	public function test_get_private_constant_from_instance(): void {
		$fixture = new PrivateAccessTestFixture();
		$value = PrivateAccess::in()->get_private_property( $fixture, 'PRIVATE_CONSTANT' );
		$this->assertSame( 'constant_value', $value );
	}


	public function test_get_private_property_throws_exception_for_non_existent_property(): void {
		$fixture = new PrivateAccessTestFixture();
		$this->expectException( TestHelperException::class );
		$this->expectExceptionMessage( 'Could not find property or constant: `nonExistentProperty`.' );
		PrivateAccess::in()->get_private_property( $fixture, 'nonExistentProperty' );
	}


	public function test_get_private_property_throws_exception_for_non_static_from_class_string(): void {
		$this->expectException( TestHelperException::class );
		$this->expectExceptionMessage( 'Getting a non-static value from a non-instantiated object is useless.' );
		PrivateAccess::in()->get_private_property( PrivateAccessTestFixture::class, 'privateProperty' );
	}


	public function test_set_private_instance_property(): void {
		$fixture = new PrivateAccessTestFixture();
		PrivateAccess::in()->set_private_property( $fixture, 'privateProperty', 'new_value' );
		$value = PrivateAccess::in()->get_private_property( $fixture, 'privateProperty' );
		$this->assertSame( 'new_value', $value );
	}


	public function test_set_protected_instance_property(): void {
		$fixture = new PrivateAccessTestFixture();
		PrivateAccess::in()->set_private_property( $fixture, 'protectedProperty', 'new_protected_value' );
		$value = PrivateAccess::in()->get_private_property( $fixture, 'protectedProperty' );
		$this->assertSame( 'new_protected_value', $value );
	}


	public function test_set_private_static_property(): void {
		// Reset to original value first
		PrivateAccess::in()->set_private_property( PrivateAccessTestFixture::class, 'privateStaticProperty', 'static_private_value' );

		PrivateAccess::in()->set_private_property( PrivateAccessTestFixture::class, 'privateStaticProperty', 'new_static_value' );
		$value = PrivateAccess::in()->get_private_property( PrivateAccessTestFixture::class, 'privateStaticProperty' );
		$this->assertSame( 'new_static_value', $value );

		// Reset back to original value
		PrivateAccess::in()->set_private_property( PrivateAccessTestFixture::class, 'privateStaticProperty', 'static_private_value' );
	}


	public function test_set_private_static_property_from_instance(): void {
		$fixture = new PrivateAccessTestFixture();
		// Reset to original value first
		PrivateAccess::in()->set_private_property( $fixture, 'privateStaticProperty', 'static_private_value' );

		PrivateAccess::in()->set_private_property( $fixture, 'privateStaticProperty', 'new_static_from_instance' );
		$value = PrivateAccess::in()->get_private_property( PrivateAccessTestFixture::class, 'privateStaticProperty' );
		$this->assertSame( 'new_static_from_instance', $value );

		// Reset back to original value
		PrivateAccess::in()->set_private_property( $fixture, 'privateStaticProperty', 'static_private_value' );
	}


	public function test_set_private_property_throws_exception_for_non_existent_property(): void {
		$fixture = new PrivateAccessTestFixture();
		$this->expectException( TestHelperException::class );
		$this->expectExceptionMessage( 'Could not reflect property:' );
		PrivateAccess::in()->set_private_property( $fixture, 'nonExistentProperty', 'value' );
	}


	public function test_set_private_property_throws_exception_for_non_static_from_class_string(): void {
		$this->expectException( TestHelperException::class );
		$this->expectExceptionMessage( 'Setting a non-static value on a non-instantiated object is useless.' );
		PrivateAccess::in()->set_private_property( PrivateAccessTestFixture::class, 'privateProperty', 'value' );
	}


	public function test_call_private_method(): void {
		$fixture = new PrivateAccessTestFixture();
		$result = PrivateAccess::in()->call_private_method( $fixture, 'privateMethod' );
		$this->assertSame( 'private_method_result', $result );
	}


	public function test_call_protected_method(): void {
		$fixture = new PrivateAccessTestFixture();
		$result = PrivateAccess::in()->call_private_method( $fixture, 'protectedMethod' );
		$this->assertSame( 'protected_method_result', $result );
	}


	public function test_call_private_method_with_parameters(): void {
		$fixture = new PrivateAccessTestFixture();
		$result = PrivateAccess::in()->call_private_method( $fixture, 'privateMethodWithParams', [ 'param1', 'param2' ] );
		$this->assertSame( 'param1-param2', $result );
	}


	public function test_call_private_static_method(): void {
		$result = PrivateAccess::in()->call_private_method( PrivateAccessTestFixture::class, 'privateStaticMethod' );
		$this->assertSame( 'static_method_result', $result );
	}


	public function test_call_private_method_from_class_string(): void {
		$result = PrivateAccess::in()->call_private_method( PrivateAccessTestFixture::class, 'privateMethod' );
		$this->assertSame( 'private_method_result', $result );
	}


	public function test_call_private_method_throws_exception_for_non_existent_method(): void {
		$fixture = new PrivateAccessTestFixture();
		$this->expectException( TestHelperException::class );
		$this->expectExceptionMessage( 'Could not reflect method:' );
		PrivateAccess::in()->call_private_method( $fixture, 'nonExistentMethod' );
	}
}

/**
 * Test fixture class for PrivateAccess tests.
 */
class PrivateAccessTestFixture {
	private const PRIVATE_CONSTANT = 'constant_value';

	private $privateProperty = 'private_value';

	protected $protectedProperty = 'protected_value';

	private static $privateStaticProperty = 'static_private_value';


	private function privateMethod(): string {
		return 'private_method_result';
	}


	protected function protectedMethod(): string {
		return 'protected_method_result';
	}


	private function privateMethodWithParams( string $param1, string $param2 ): string {
		return $param1 . '-' . $param2;
	}


	private static function privateStaticMethod(): string {
		return 'static_method_result';
	}
}
