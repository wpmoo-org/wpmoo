<?php
/**
 * Switch control (checkbox with role="switch").
 *
 * @package WPMoo\\Fields\\SwitchField
 */

namespace WPMoo\Fields\SwitchField;

use WPMoo\Fields\Checkbox\Checkbox as BaseCheckbox;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Switch is a semantic variant of checkbox.
 */
class SwitchField extends BaseCheckbox {
	/**
	 * Render switch (checkbox + role="switch").
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$attributes         = $this->attributes();
		$attributes['role'] = 'switch';
		$this->override_attributes( $attributes );

		return parent::render( $name, $value );
	}

	/**
	 * Internal helper to override attributes array.
	 *
	 * @param array<string,mixed> $attributes Attrs.
	 * @return void
	 */
	protected function override_attributes( array $attributes ): void {
		$this->attributes = array_merge( $this->attributes, $attributes );
	}
}
