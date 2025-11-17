<?php
/**
 * Basic smoke tests for framework bootstrap.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Ensures the framework core autoloads.
 */
final class SmokeTest extends TestCase {

	public function testAutoloadsFrameworkCore(): void {
		$this->assertTrue( class_exists( \WPMoo\Moo::class ) );
	}
}
