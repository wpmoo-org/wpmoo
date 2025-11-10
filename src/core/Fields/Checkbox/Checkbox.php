<?php
/**
 * Checkbox control (boolean switch).
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
namespace WPMoo\Fields\Checkbox;

use WPMoo\Fields\BaseField;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Single checkbox field.
 */
class Checkbox extends BaseField {
	/**
	 * Render a checkbox with label wrapper.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value (truthy for checked).
	 * @return string
	 */
	public function render( $name, $value ) {
		$label_text = (string) $this->label();
		$label_html = '' !== $label_text ? $this->esc_html( $label_text ) : '';

		$attributes          = $this->attributes();
		$attributes['name']  = $name;
		$attributes['type']  = 'checkbox';
		$attributes['value'] = isset( $attributes['value'] ) ? (string) $attributes['value'] : '1';

		$checked = ( '1' === (string) $value || 1 === $value || true === $value || 'on' === (string) $value );
		if ( $checked ) {
			$attributes['checked'] = true;
		}

		$attr   = $this->render_attributes( $attributes );
		$before = $this->before_html();
		$after  = $this->after_html();

		$html  = '';
		$html .= $before;
		$html .= '<label>';
		$html .= sprintf( '<input %s>', $attr );
		if ( '' !== $label_html ) {
			$html .= ' ' . $label_html;
		}
		$help = $this->help_html();
		if ( '' !== $help ) {
			$html .= '<small>' . $help . '</small>';
		}
		$html .= '</label>';
		$html .= $after;

		return $html;
	}

	/**
	 * Sanitize boolean checkbox.
	 *
	 * @param mixed $value Raw value.
	 * @return string '1' when checked, '' otherwise.
	 */
	public function sanitize( $value ) {
		$checked = ( '1' === (string) $value || 1 === $value || true === $value || 'on' === (string) $value );
		return $checked ? '1' : '';
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
}
