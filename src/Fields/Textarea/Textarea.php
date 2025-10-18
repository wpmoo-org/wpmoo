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

use WPMoo\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		$attributes = $this->build_attributes();
		$value      = null !== $value ? $value : $this->default();
		$value      = null !== $value ? $this->esc_html( $value ) : '';

		return sprintf(
			'<textarea name="%s" id="%s"%s>%s</textarea>',
			$this->esc_attr( $name ),
			$this->esc_attr( $this->id() ),
			$attributes,
			$value
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
