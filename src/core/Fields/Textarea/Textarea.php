<?php
/**
 * Textarea control (multiline input).
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Textarea;

use WPMoo\Fields\BaseField;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Multiline textarea field with safe escaping/sanitization.
 */
class Textarea extends BaseField {
	/**
	 * Render the textarea and its label wrapper.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$label_text = (string) $this->label();
		$label_html = '' !== $label_text ? $this->esc_html( $label_text ) : '';

		$attributes          = $this->attributes();
		$attributes['name']  = $name;
		$attributes['rows']  = isset( $attributes['rows'] ) ? $attributes['rows'] : 5;
		$attributes['cols']  = isset( $attributes['cols'] ) ? $attributes['cols'] : 40;

		$attr = $this->render_attributes( $attributes );

		$before = $this->before_html();
		$after  = $this->after_html();

		$val = is_scalar( $value ) ? (string) $value : '';
		$val = function_exists( 'esc_textarea' ) ? esc_textarea( $val ) : $this->esc_html( $val );

		$html  = '';
		$html .= $before;
		$html .= '<label>';
		if ( '' !== $label_html ) {
			$html .= $label_html;
		}
		$html .= sprintf( '<textarea %s>%s</textarea>', $attr, $val );
		$help = $this->help_html();
		if ( '' !== $help ) {
			$html .= '<small>' . $help . '</small>';
		}
		$html .= '</label>';
		$html .= $after;

		return $html;
	}

	/**
	 * Build attribute string safely.
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 * @return string
	 */
	protected function render_attributes( array $attributes ): string {
		$pairs = array();
		foreach ( $attributes as $key => $val ) {
			$k = $this->esc_attr( (string) $key );
			if ( true === $val ) {
				$pairs[] = $k;
			} elseif ( false === $val || null === $val ) {
				continue;
			} else {
				$pairs[] = sprintf( '%s="%s"', $k, $this->esc_attr( (string) $val ) );
			}
		}
		return implode( ' ', $pairs );
	}

	/**
	 * Sanitize multiline text.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		if ( is_string( $value ) && function_exists( 'sanitize_textarea_field' ) ) {
			return sanitize_textarea_field( $value );
		}
		return parent::sanitize( $value );
	}
}
