<?php
/**
 * Tests for WPMoo\Support\Arr helper.
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPMoo\Support\Arr;

/**
 * Arr helper test suite.
 */
final class ArrTest extends TestCase {

	/**
	 * It should get nested values using dot-notation with default fallback.
	 */
	public function testGetWithDefault(): void {
		$data = [ 'a' => [ 'b' => [ 'c' => 123 ] ] ];
		$this->assertSame( 123, Arr::get( $data, 'a.b.c' ) );
		$this->assertSame( 'x', Arr::get( $data, 'a.b.d', 'x' ) );
		$this->assertSame( 'y', Arr::get( null, 'a', 'y' ) );
	}

	/**
	 * It should set nested values using dot-notation creating arrays on demand.
	 */
	public function testSet(): void {
		$data = [];
		Arr::set( $data, 'user.profile.name', 'Ada' );
		$this->assertSame( 'Ada', $data['user']['profile']['name'] );
	}
}
