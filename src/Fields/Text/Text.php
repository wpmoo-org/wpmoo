<?php
/**
 * Text input field.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
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

		if ( isset( $attributes['type'] ) ) {
			unset( $attributes['type'] );
		}

		$value = null !== $value ? $value : $this->default();
		$value = null !== $value ? $this->esc_attr( $value ) : '';

		return sprintf(
			'<input type="%1$s" name="%2$s" id="%3$s" value="%4$s"%5$s />',
			$this->esc_attr( $type ),
			$this->esc_attr( $name ),
			$this->esc_attr( $this->id() ),
			$value,
			$this->compile_attributes( $attributes )
		);
	}
}
