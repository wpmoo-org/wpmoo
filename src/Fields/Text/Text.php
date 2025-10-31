<?php
/**
 * Text input field.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Text;

use WPMoo\Fields\BaseField as Field;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Renders a single-line text input.
 */
class Text extends Field {

	/**
	 * Render the field HTML.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$attributes = $this->attributes();
		$type       = isset( $attributes['type'] ) ? $attributes['type'] : 'text';

		// Remove consumed attributes from the map so they are not rendered on the input tag.
		if ( isset( $attributes['type'] ) ) {
			unset( $attributes['type'] );
		}

		// Optional icon support: pass via attributes 'icon' and (optionally) 'icon_position' => left|right.
		$icon_class   = '';
		$icon_position = 'left';
		if ( isset( $attributes['icon'] ) && is_string( $attributes['icon'] ) ) {
			$icon_class = trim( (string) $attributes['icon'] );
			unset( $attributes['icon'] );
		}
        if ( isset( $attributes['icon_position'] ) && is_string( $attributes['icon_position'] ) ) {
            $pos = strtolower( (string) $attributes['icon_position'] );
            $icon_position = in_array( $pos, array( 'left', 'right' ), true ) ? $pos : 'left';
            unset( $attributes['icon_position'] );
        }

		$value = null !== $value ? $value : $this->default();
		$value = null !== $value ? $this->esc_attr( $value ) : '';

        // If icon is present, ensure the input has enough inner padding as a safety net,
        // in case theme styles override our framework CSS. This inline style augments the
        // SCSS-based spacing from `.wpmoo-input--icon-*`.
        if ( '' !== $icon_class ) {
            $pad = 'left' === $icon_position ? 'padding-left:2rem;' : 'padding-right:2rem;';
            if ( isset( $attributes['style'] ) && is_string( $attributes['style'] ) ) {
                $attributes['style'] = rtrim( (string) $attributes['style'] ) . ' ' . $pad;
            } else {
                $attributes['style'] = $pad;
            }
        }

        $input = sprintf(
            '<input class="wpmoo-input__control" type="%1$s" name="%2$s" id="%3$s" value="%4$s"%5$s />',
            $this->esc_attr( $type ),
            $this->esc_attr( $name ),
            $this->esc_attr( $this->id() ),
            $value,
            $this->compile_attributes( $attributes )
        );

		if ( '' === $icon_class ) {
			// No icon requested; return plain input.
			return $input;
		}

		// Icon markup. Accept raw class (e.g., 'dashicons dashicons-admin-users') or any icon font class.
		$icon_html = sprintf(
			'<span class="wpmoo-input__icon %1$s" aria-hidden="true"></span>',
			$this->esc_attr( $icon_class )
		);

		// Wrapper with positioning class for styling.
		$wrapper_classes = array( 'wpmoo-input', 'wpmoo-input--with-icon', 'wpmoo-input--icon-' . $icon_position );

		return sprintf(
			'<span class="%1$s">%2$s%3$s</span>',
			$this->esc_attr( implode( ' ', $wrapper_classes ) ),
			// Left vs right placement handled by CSS via order; output in DOM order matching position.
			'left' === $icon_position ? ( $icon_html . $input ) : ( $input . $icon_html ),
			''
		);
	}
}
