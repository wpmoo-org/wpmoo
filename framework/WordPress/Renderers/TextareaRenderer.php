<?php

namespace WPMoo\WordPress\Renderers;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * WordPress renderer for textarea field.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class TextareaRenderer extends BaseRenderer {
	/**
	 * Render a textarea field.
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
		
		$rows = method_exists( $field, 'get_rows' ) ? $field->get_rows() : 5;
		$cols = method_exists( $field, 'get_cols' ) ? $field->get_cols() : null;

		$attributes = ' rows="' . esc_attr( $rows ) . '"';
		if ( null !== $cols ) {
			$attributes .= ' cols="' . esc_attr( $cols ) . '"';
		}

		$input_html = '<div class="form-group"><textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="wpmoo-textarea input-group"' . $attributes . '>' . esc_textarea( $value ) . '</textarea></div>';

		return $this->renderFieldWrapper( $field, $unique_slug, $value, $input_html );
	}
}
