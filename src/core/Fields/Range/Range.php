<?php
/**
 * Range control (slider input).
 *
 * @package WPMoo\\Fields\\Range
 */

namespace WPMoo\Fields\Range;

use WPMoo\Fields\BaseField;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Range slider field.
 */
class Range extends BaseField {
	/**
	 * Render the input type="range" control.
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$label_text = (string) $this->label();
		$label_html = '' !== $label_text ? $this->esc_html( $label_text ) : '';

		$attributes          = $this->attributes();
		$attributes['name']  = $name;
		$attributes['type']  = 'range';
		$attributes['value'] = is_scalar( $value ) ? (string) $value : '0';

		$attr   = $this->render_attributes( $attributes );
		$before = $this->before_html();
		$after  = $this->after_html();

		$html  = '';
		$html .= $before;
		$html .= '<label>';
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
	 * Sanitize numeric value within optional min/max.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize( $value ) {
		$v = is_numeric( $value ) ? (float) $value : 0.0;
		$min = $this->attribute( 'min', null );
		$max = $this->attribute( 'max', null );
		if ( is_numeric( $min ) ) {
			$v = max( (float) $min, $v );
		}
		if ( is_numeric( $max ) ) {
			$v = min( (float) $max, $v );
		}
		$step = $this->attribute( 'step', null );
		if ( is_numeric( $step ) && (float) $step > 0 ) {
			$decimals = strpos( (string) $step, '.' ) !== false ? strlen( (string) $step ) - strpos( (string) $step, '.' ) - 1 : 0;
			$v       = round( $v / (float) $step ) * (float) $step;
			return number_format( $v, $decimals, '.', '' );
		}
		return (string) $v;
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
				$pairs[] = sprintf( '%s=\"%s\"', $k, $this->esc_attr( (string) $val ) );
			}
		}
		return implode( ' ', $pairs );
	}
}
