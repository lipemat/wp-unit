<?php

abstract class WP_CLI_UnitTestCase extends WP_UnitTestCase {
	/**
	 * Hold success messages added during this test.
	 *
	 * @var string[]
	 */
	public $success = [];

	/**
	 * Hold line messages added during this test.
	 *
	 * @var string[]
	 */
	public $line = [];

	/**
	 * Hold warning messages added during this test.
	 *
	 * @var string[]
	 */
	public $warning = [];

	/**
	 * Hold error messages added during this test.
	 *
	 * @var string[]
	 */
	public $error = [];


	/**
	 * @throws Exception
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( '\WP_CLI'  )) {
			require __DIR__ . '/mock-wp-cli.php';
		}
		if( method_exists( '\WP_CLI', '__provide_test_case' ) ) {
			\WP_CLI::__provide_test_case( $this );
		}

	}


	public function tearDown(): void {
		$this->reset_output();
		parent::tearDown();
	}


	/**
	 * Reset the variables stored during this test.
	 *
	 * @return void
	 */
	protected function reset_output(): void {
		$this->success = [];
		$this->error = [];
		$this->line = [];
		$this->warning = [];
	}
}
