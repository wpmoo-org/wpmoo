<?php
/**
 * Input field (text by default).
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Input;

use WPMoo\Fields\Abstracts\BaseField;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Input field (text-like control).
 */
class Input extends BaseField {
	/**
	 * Render the input control with a label wrapper (Pico-friendly).
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$label_text = (string) $this->label();
		$label_html = '' !== $label_text ? $this->esc_html( $label_text ) : '';

		$attributes            = $this->attributes();
		$attributes['name']    = $name;
		$attributes['type']    = isset( $attributes['type'] ) && is_string( $attributes['type'] ) ? $attributes['type'] : 'text';
		$attributes['value']   = is_scalar( $value ) ? (string) $value : '';

		$attr = $this->render_attributes( $attributes );

		$before = $this->before_html();
		$after  = $this->after_html();

		$html   = '';
		$html  .= $before;
		$html  .= '<label>';
		if ( '' !== $label_html ) {
			$html .= $label_html;
		}
		$html .= sprintf( '<input %s>', $attr );
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
			$k = $this->esc_attr( $key );
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
	 * Sanitize simple text input.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		if ( is_string( $value ) && function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $value );
		}
		return parent::sanitize( $value );
	}
}
