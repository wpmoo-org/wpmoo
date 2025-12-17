<?php

namespace WPMoo\Tests\Unit\Field\Type;

use PHPUnit\Framework\TestCase;
use WPMoo\Field\Type\Input;

class InputTest extends TestCase {
	public function test_defaults() {
		$field = new Input( 'test_input' );
		$this->assertEquals( 'test_input', $field->get_id() );
		$this->assertEquals( 'text', $field->get_type() );
		$this->assertNull( $field->get_min() );
		$this->assertNull( $field->get_max() );
	}

	public function test_setters() {
		$field = new Input( 'test_input' );
		$field->type( 'number' )
			->min( 5 )
			->max( 10 )
			->step( 1 )
			->required();

		$this->assertEquals( 'number', $field->get_type() );
		$this->assertEquals( 5, $field->get_min() );
		$this->assertEquals( 10, $field->get_max() );
		$this->assertEquals( 1, $field->get_step() );

		$validation = $field->validate( '' );
		$this->assertFalse( $validation['valid'] );
	}
}
