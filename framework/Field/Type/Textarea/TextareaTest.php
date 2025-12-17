<?php

namespace WPMoo\Field\Type\Textarea;

use PHPUnit\Framework\TestCase;

class TextareaTest extends TestCase {
	public function test_defaults() {
		$field = new Textarea( 'test_textarea' );
		$this->assertEquals( 5, $field->get_rows() );
		$this->assertNull( $field->get_cols() );
	}

	public function test_setters() {
		$field = new Textarea( 'test_textarea' );
		$field->rows( 10 )->cols( 50 );

		$this->assertEquals( 10, $field->get_rows() );
		$this->assertEquals( 50, $field->get_cols() );
	}
}
