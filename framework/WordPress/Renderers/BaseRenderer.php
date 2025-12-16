<?php

namespace WPMoo\WordPress\Renderers;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * Base renderer implementation.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
abstract class BaseRenderer implements FieldRendererInterface {
	/**
	 * Render a field wrapper with label and input.
	 *
	 * @param FieldInterface $field The field to render.
	 * @param string         $unique_slug The unique slug for the page.
	 * @param mixed          $value The current value of the field.
	 * @param string         $input_html The HTML for the input element.
	 * @return string The rendered HTML.
	 */
	protected function renderFieldWrapper( FieldInterface $field, string $unique_slug, $value, string $input_html ): string {
		$field_id = $field->get_id();
		$label = method_exists( $field, 'get_label' ) ? $field->get_label() : '';
		$field_name = $unique_slug . '[' . $field_id . ']';

		$html = '<div class="field-wrapper" data-field-id="' . esc_attr( $field_id ) . '">';

		if ( ! empty( $label ) ) {
			$html .= '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $label ) . '</label>';
		}

		$html .= $input_html;
		$html .= '</div>';

		return $html;
	}
}
