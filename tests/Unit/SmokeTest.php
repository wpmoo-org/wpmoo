<?php
/**
 * Basic smoke tests for framework bootstrap.
 *
 * @package WPMoo\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Ensures the framework core autoloads.
 */
final class SmokeTest extends TestCase {

	/**
	 * Test that the main framework facade class exists.
	 */
	public function testAutoloadsFrameworkCore(): void {
		$this->assertTrue( class_exists( \WPMoo\Moo::class ) );
	}
}
