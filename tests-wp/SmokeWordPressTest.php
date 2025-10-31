<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Minimal WordPress integration smoke tests.
 */
final class SmokeWordPressTest extends TestCase {

	public function testWordPressFunctionsAvailable(): void {
		$this->assertTrue( function_exists( 'add_filter' ) );
		$this->assertTrue( function_exists( 'do_action' ) );
	}

	public function testFrameworkLoadsUnderWordPress(): void {
		$this->assertTrue( class_exists( \WPMoo\Moo::class ) );
	}
}
