<?php
/**
 * Checkbox field implementation.
 *
 * WPMoo — WordPress Micro Object-Oriented Framework.
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 */

namespace WPMoo\Fields\Checkbox;

use WPMoo\Fields\Field;

/**
 * Renders a boolean checkbox toggle.
 */
class Checkbox extends Field {

	/**
	 * Render the field HTML.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$is_checked = null !== $value ? (bool) $value : (bool) $this->default();
		$attributes = $this->build_attributes();

		return sprintf(
			'<input type="checkbox" name="%s" id="%s" value="1"%s%s />',
			$this->esc_attr( $name ),
			$this->esc_attr( $this->id() ),
			$is_checked ? ' checked="checked"' : '',
			$attributes
		);
	}

	/**
	 * Sanitize checkbox value.
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	public function sanitize( $value ) {
		return $value ? 1 : 0;
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
