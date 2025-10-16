<?php
/**
 * Text input field.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 */

namespace WPMoo\Fields\Text;

use WPMoo\Fields\Field;

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
		$attributes = $this->build_attributes();
		$value      = null !== $value ? $value : $this->default();
		$value      = null !== $value ? $this->esc_attr( $value ) : '';

		return sprintf(
			'<input type="text" name="%s" id="%s" value="%s"%s />',
			$this->esc_attr( $name ),
			$this->esc_attr( $this->id() ),
			$value,
			$attributes
		);
	}

	/**
	 * Compile additional HTML attributes.
	 *
	 * @return string
	 */
	protected function build_attributes() {
		if ( empty( $this->args() ) ) {
			return '';
		}

		$output = '';

		foreach ( $this->args() as $attribute => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$output .= ' ' . $this->esc_attr( $attribute );
				}
				continue;
			}

			$output .= sprintf(
				' %s="%s"',
				$this->esc_attr( $attribute ),
				$this->esc_attr( $value )
			);
		}

		return $output;
	}
}
