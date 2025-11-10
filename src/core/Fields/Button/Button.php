<?php
/**
 * Button pseudo-field.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
namespace WPMoo\Fields\Button;

use WPMoo\Fields\BaseField;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Button pseudo-field (non-persistent control).
 */
class Button extends BaseField {
	/**
	 * Render a button element; not persisted.
	 *
	 * @param string $name  Input name attribute (unused).
	 * @param mixed  $value Current value (unused).
	 * @return string
	 */
	public function render( $name, $value ) {
		$text = (string) $this->label();
		if ( '' === $text ) {
			$text = function_exists( '__' ) ? __( 'Submit', 'wpmoo' ) : 'Submit';
		}
		$attributes = $this->attributes();
		$attributes['type'] = isset( $attributes['type'] ) ? $attributes['type'] : 'button';

		$attr = $this->render_attributes( $attributes );

		$before = $this->before_html();
		$after  = $this->after_html();
		return $before . sprintf( '<button %s>%s</button>', $attr, $this->esc_html( $text ) ) . $after;
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
}
