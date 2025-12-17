<?php

namespace WPMoo\WordPress\Renderers;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * WordPress renderer for input field.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class InputRenderer extends BaseRenderer {
	/**
	 * Render an input field.
	 *
	 * @param FieldInterface $field The field to render.
	 * @param string         $unique_slug The unique slug for the page.
	 * @param mixed          $value The current value of the field.
	 * @return string The rendered HTML.
	 */
	public function render( FieldInterface $field, string $unique_slug, $value ): string {
		$field_id = $field->get_id();
		$field_name = $unique_slug . '[' . $field_id . ']';
		$placeholder = method_exists( $field, 'get_placeholder' ) ? $field->get_placeholder() : '';
		
		$type = method_exists( $field, 'get_type' ) ? $field->get_type() : 'text';
		$min = method_exists( $field, 'get_min' ) ? $field->get_min() : null;
		$max = method_exists( $field, 'get_max' ) ? $field->get_max() : null;
		$step = method_exists( $field, 'get_step' ) ? $field->get_step() : null;

		$attributes = '';
		if ( null !== $min ) {
			$attributes .= ' min="' . esc_attr( $min ) . '"';
		}
		if ( null !== $max ) {
			$attributes .= ' max="' . esc_attr( $max ) . '"';
		}
		if ( null !== $step ) {
			$attributes .= ' step="' . esc_attr( $step ) . '"';
		}

		$input_html = '<div class="form-group"><input type="' . esc_attr( $type ) . '" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="wpmoo-input input-group"' . $attributes . '></div>';

		return $this->renderFieldWrapper( $field, $unique_slug, $value, $input_html );
	}
}
