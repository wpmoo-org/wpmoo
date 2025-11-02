<?php
/**
 * Radio group control.
 *
 * @package WPMoo\Fields\Radio
 */

namespace WPMoo\Fields\Radio;

use WPMoo\Fields\BaseField;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Radio buttons (single choice from options).
 */
class Radio extends BaseField {
	/**
	 * Render radio options with a wrapping fieldset+legend for accessibility.
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$label_text = (string) $this->label();
		$label_html = '' !== $label_text ? $this->esc_html( $label_text ) : '';
		$options    = $this->normalize_options( $this->options() );
		$current    = is_scalar( $value ) ? (string) $value : '';

		$before = $this->before_html();
		$after  = $this->after_html();

		$html  = '';
		$html .= $before;
		$html .= '<fieldset>';
		if ( '' !== $label_html ) {
			$html .= '<legend>' . $label_html . '</legend>';
		}
		foreach ( $options as $opt_value => $opt_label ) {
			$id       = $this->esc_attr( $this->id() . '-' . $opt_value );
			$checked  = ( (string) $opt_value === $current ) ? ' checked' : '';
			$opt_html = sprintf(
				'<label for="%1$s"><input id="%1$s" type="radio" name="%2$s" value="%3$s"%4$s> %5$s</label>',
				$id,
				$this->esc_attr( $name ),
				$this->esc_attr( (string) $opt_value ),
				$checked,
				$this->esc_html( (string) $opt_label )
			);
			$html .= $opt_html;
		}
		$help = $this->help_html();
		if ( '' !== $help ) {
			$html .= '<small>' . $help . '</small>';
		}
		$html .= '</fieldset>';
		$html .= $after;

		return $html;
	}

	/**
	 * Sanitize to a known option key.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize( $value ) {
		$options = $this->normalize_options( $this->options() );
		$keys    = array_map( 'strval', array_keys( $options ) );
		$v      = (string) $value;
		return in_array( $v, $keys, true ) ? $v : '';
	}

	/**
	 * Normalize options to value => label pairs.
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
}
