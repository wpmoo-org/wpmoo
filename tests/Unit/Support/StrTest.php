<?php
/**
 * Tests for WPMoo\Support\Str helper.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPMoo\Support\Str;

/**
 * Str helper test suite.
 */
final class StrTest extends TestCase {

	/**
	 * It should detect prefixes and suffixes correctly.
	 */
	public function testStartsAndEndsWith(): void {
		$this->assertTrue( Str::startsWith( 'framework', 'frame' ) );
		$this->assertFalse( Str::startsWith( 'framework', 'work' ) );

		$this->assertTrue( Str::endsWith( 'framework', 'work' ) );
		$this->assertFalse( Str::endsWith( 'framework', 'frame' ) );
	}

	/**
	 * It should return true for empty needle in contains/starts/ends.
	 */
	public function testEmptyNeedleSemantics(): void {
		$this->assertTrue( Str::contains( 'abc', '' ) );
		$this->assertTrue( Str::startsWith( 'abc', '' ) );
		$this->assertTrue( Str::endsWith( 'abc', '' ) );
	}

	/**
	 * It should generate slugs without WordPress available.
	 */
	public function testSlugFallback(): void {
		$this->assertSame( 'hello-world', Str::slug( ' Hello  World ' ) );
		$this->assertSame( 'wpmoo', Str::slug( 'WPMoo' ) );
		$this->assertSame( '', Str::slug( '' ) );
	}
}
