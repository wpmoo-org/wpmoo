<?php

namespace WPMoo\WordPress\Renderers;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * WordPress renderer for toggle field.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class ToggleRenderer extends BaseRenderer {
	/**
	 * Render a toggle field.
	 *
	 * @param FieldInterface $field The field to render.
	 * @param string         $unique_slug The unique slug for the page.
	 * @param mixed          $value The current value of the field.
	 * @return string The rendered HTML.
	 */
	public function render( FieldInterface $field, string $unique_slug, $value ): string {
		$field_id = $field->get_id();
		$field_name = $unique_slug . '[' . $field_id . ']';
		$checked = checked( $value, '1', false ); // value="1" typically sends "1" or nothing.
		
		$on_label = method_exists( $field, 'get_on_label' ) ? $field->get_on_label() : 'On';
		$off_label = method_exists( $field, 'get_off_label' ) ? $field->get_off_label() : 'Off';

		$input_html = '<div class="form-group display-flex align-items-center gap-2">';
		$input_html .= '<span class="off-label">' . esc_html( $off_label ) . '</span>';
		$input_html .= '<input type="checkbox" role="switch" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . $checked . ' class="wpmoo-toggle form-switch">';
		$input_html .= '<span class="on-label">' . esc_html( $on_label ) . '</span>';
		$input_html .= '</div>';

		return $this->renderFieldWrapper( $field, $unique_slug, $value, $input_html );
	}
}
