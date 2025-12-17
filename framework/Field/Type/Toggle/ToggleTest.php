<?php

namespace WPMoo\Field\Type\Toggle;

use PHPUnit\Framework\TestCase;

class ToggleTest extends TestCase {
	public function test_defaults() {
		$field = new Toggle( 'test_toggle' );
		$this->assertEquals( 'On', $field->get_on_label() );
		$this->assertEquals( 'Off', $field->get_off_label() );
	}

	public function test_setters() {
		$field = new Toggle( 'test_toggle' );
		$field->on_label( 'Yes' )->off_label( 'No' );

		$this->assertEquals( 'Yes', $field->get_on_label() );
		$this->assertEquals( 'No', $field->get_off_label() );
	}
}
