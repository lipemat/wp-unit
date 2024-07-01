<?php

if ( class_exists( 'PHPUnit\Runner\Version' ) && version_compare( PHPUnit\Runner\Version::id(), '6.0', '>=' ) ) {

	class_alias( 'PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase' );
	class_alias( 'PHPUnit\Framework\Exception', 'PHPUnit_Framework_Exception' );
	class_alias( 'PHPUnit\Framework\ExpectationFailedException', 'PHPUnit_Framework_ExpectationFailedException' );

	if ( class_exists( 'PHPUnit\Framework\Error\Deprecated' ) ) {
		class_alias( 'PHPUnit\Framework\Error\Deprecated', 'PHPUnit_Framework_Error_Deprecated' );
	}
	if ( class_exists( 'PHPUnit\Framework\Error\Notice' ) ) {
		class_alias( 'PHPUnit\Framework\Error\Notice', 'PHPUnit_Framework_Error_Notice' );
	}
	if ( class_exists( 'PHPUnit\Framework\Error\Warning' ) ) {
		class_alias( 'PHPUnit\Framework\Error\Warning', 'PHPUnit_Framework_Error_Warning' );
	}

	class_alias( 'PHPUnit\Framework\Test', 'PHPUnit_Framework_Test' );
	class_alias( 'PHPUnit\Framework\AssertionFailedError', 'PHPUnit_Framework_AssertionFailedError' );
	class_alias( 'PHPUnit\Framework\TestSuite', 'PHPUnit_Framework_TestSuite' );

	if ( class_exists( 'PHPUnit\Framework\TestListener' ) ) {
		class_alias( 'PHPUnit\Framework\TestListener', 'PHPUnit_Framework_TestListener' );
	}
	class_alias( 'PHPUnit\Util\GlobalState', 'PHPUnit_Util_GlobalState' );
	if ( class_exists( 'PHPUnit\Util\Getopt' ) ) {
		class_alias( 'PHPUnit\Util\Getopt', 'PHPUnit_Util_Getopt' );
	}
}
