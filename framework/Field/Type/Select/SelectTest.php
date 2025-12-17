<?php

namespace WPMoo\Field\Type\Select;

use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase {
	public function test_defaults() {
		$field = new Select( 'test_select' );
		$this->assertFalse( $field->get_multiple() );
	}

	public function test_validation() {
		$field = new Select( 'test_select' );
		$field->options( [
			'a' => 'Option A',
			'b' => 'Option B',
		] );

		// Valid
		$result = $field->validate( 'a' );
		$this->assertTrue( $result['valid'] );

		// Invalid
		$result = $field->validate( 'c' );
		$this->assertFalse( $result['valid'] );
	}

	public function test_multiple_validation() {
		$field = new Select( 'test_select' );
		$field->multiple()->options( [
			'a' => 'Option A',
			'b' => 'Option B',
		] );

		// Valid
		$result = $field->validate( ['a', 'b'] );
		$this->assertTrue( $result['valid'] );

		// Invalid value
		$result = $field->validate( ['a', 'c'] );
		$this->assertFalse( $result['valid'] );

		// Invalid format (string instead of array)
		$result = $field->validate( 'a' );
		$this->assertFalse( $result['valid'] );
	}
}
