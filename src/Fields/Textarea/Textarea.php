<?php
/**
 * Textarea field implementation.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Fields\Textarea;

use WPMoo\Fields\BaseField as Field;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Renders a multi-line textarea input.
 */
class Textarea extends Field {

	/**
	 * Render the field HTML.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$attributes = $this->attributes();
		$value      = null !== $value ? $value : $this->default();
		$value      = null !== $value ? $this->esc_html( $value ) : '';

		return sprintf(
			'<textarea name="%1$s" id="%2$s"%3$s>%4$s</textarea>',
			$this->esc_attr( $name ),
			$this->esc_attr( $this->id() ),
			$this->compile_attributes( $attributes ),
			$value
		);
	}
}
