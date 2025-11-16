<?php
/**
 * Select control (single or multiple).
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields\Select;

use WPMoo\Fields\Abstracts\BaseField;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Select dropdown field with safe options filtering.
 */
class Select extends BaseField {
	/**
	 * Render the select element with label wrapper.
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Current value (string|array for multiple).
	 * @return string
	 */
	public function render( $name, $value ) {
		$label_text = (string) $this->label();
		$label_html = '' !== $label_text ? $this->esc_html( $label_text ) : '';

		$attributes         = $this->attributes();
		$attributes['name'] = $name . ( $this->multiple ? '[]' : '' );

		$attr = $this->render_attributes( $attributes );

		$before = $this->before_html();
		$after  = $this->after_html();

		$options = $this->normalize_options( $this->options() );
		$values  = $this->normalize_value( $value, $options );

		$html  = '';
		$html .= $before;
		$html .= '<label>';
		if ( '' !== $label_html ) {
			$html .= $label_html;
		}
		$html .= sprintf( '<select %s>', $attr );
		foreach ( $options as $opt_value => $opt_label ) {
			$selected = in_array( (string) $opt_value, $values, true ) ? ' selected' : '';
			$html    .= sprintf( '<option value="%s"%s>%s</option>', $this->esc_attr( (string) $opt_value ), $selected, $this->esc_html( (string) $opt_label ) );
		}
		$html .= '</select>';
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
	 * Sanitize selection(s) to known option keys.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		$options = $this->normalize_options( $this->options() );
		$keys    = array_map( 'strval', array_keys( $options ) );

		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $v ) {
				$v = (string) $v;
				if ( in_array( $v, $keys, true ) ) {
					$clean[] = $v;
				}
			}
			return $clean;
		}

		$v = (string) $value;
		return in_array( $v, $keys, true ) ? $v : '';
	}

	/**
	 * Normalize options array to value => label pairs.
	 *
	 * @param array<int|string,mixed> $options Raw options.
	 * @return array<string,string>
	 */
	protected function normalize_options( array $options ): array {
		$normalized = array();
		foreach ( $options as $key => $val ) {
			if ( is_array( $val ) && isset( $val['value'] ) ) {
				$k = (string) $val['value'];
				$l = isset( $val['label'] ) ? (string) $val['label'] : $k;
				$normalized[ $k ] = $l;
				continue;
			}
			$k = is_int( $key ) ? (string) $val : (string) $key;
			$l = is_int( $key ) ? (string) $val : (string) $val;
			$normalized[ $k ] = $l;
		}
		return $normalized;
	}

	/**
	 * Normalize current value(s) to an array of strings.
	 *
	 * @param mixed               $value   Raw value.
	 * @param array<string,string> $options Normalized options for filtering.
	 * @return array<int,string>
	 */
	protected function normalize_value( $value, array $options ): array {
		$keys = array_map( 'strval', array_keys( $options ) );
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $v ) {
				$v = (string) $v;
				if ( in_array( $v, $keys, true ) ) {
					$out[] = $v;
				}
			}
			return $out;
		}
		$v = (string) $value;
		return in_array( $v, $keys, true ) ? array( $v ) : array();
	}
}
