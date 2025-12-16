<?php

namespace WPMoo\WordPress\Renderers;

use WPMoo\Field\Interfaces\FieldInterface;

/**
 * WordPress renderer for select field.
 *
 * @package WPMoo\WordPress\Renderers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class SelectRenderer extends BaseRenderer {
	/**
	 * Render a select field.
	 *
	 * @param FieldInterface $field The field to render.
	 * @param string         $unique_slug The unique slug for the page.
	 * @param mixed          $value The current value of the field.
	 * @return string The rendered HTML.
	 */
	public function render( FieldInterface $field, string $unique_slug, $value ): string {
		$field_id = $field->get_id();
		$field_name = $unique_slug . '[' . $field_id . ']';

		// Get options if the field has them
		$options = array();
		if ( method_exists( $field, 'get_options' ) ) {
			$options = $field->get_options();
		}

		$select_html = '<div class="form-group"><select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" class="wpmoo-select input-group">';

		foreach ( $options as $option_value => $option_label ) {
			$selected = selected( $value, $option_value, false );
			$select_html .= '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . esc_html( $option_label ) . '</option>';
		}

		$select_html .= '</select></div>';

		return $this->renderFieldWrapper( $field, $unique_slug, $value, $select_html );
	}
}
