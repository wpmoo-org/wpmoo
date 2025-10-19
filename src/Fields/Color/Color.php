<?php
/**
 * Color picker field implementation.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Fields\Color;

use WPMoo\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a color picker input leveraging the WordPress color picker.
 */
class Color extends Field {

	/**
	 * Tracks whether assets have been enqueued.
	 *
	 * @var bool
	 */
	protected static $assets_enqueued = false;

	/**
	 * Render the field HTML.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$this->maybe_enqueue_assets();

		$value      = null !== $value ? $value : $this->default();
		$value      = null !== $value ? $this->esc_attr( $value ) : '';
		$attributes = $this->build_attributes();

		return sprintf(
			'<input type="text" name="%s" id="%s" value="%s" class="wpmoo-color-field"%s />',
			$this->esc_attr( $name ),
			$this->esc_attr( $this->id() ),
			$value,
			$attributes
		);
	}

	/**
	 * Sanitize color value.
	 *
	 * @param mixed $value Input value.
	 * @return string|null
	 */
	public function sanitize( $value ) {
		if ( is_string( $value ) ) {
			if ( function_exists( 'sanitize_hex_color' ) ) {
				$sanitized = sanitize_hex_color( $value );
				if ( null !== $sanitized ) {
					return $sanitized;
				}
			}
		}

		return parent::sanitize( $value );
	}

	/**
	 * Ensure the WordPress color picker assets are loaded.
	 *
	 * @return void
	 */
	protected function maybe_enqueue_assets() {
		if ( self::$assets_enqueued ) {
			return;
		}

		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style( 'wp-color-picker' );
		}

		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script( 'wp-color-picker' );
		}

		if ( function_exists( 'add_action' ) ) {
			add_action(
				'admin_footer',
				static function () {
					echo '<script>document.addEventListener("DOMContentLoaded",function(){if(window?.jQuery){jQuery(".wpmoo-color-field").wpColorPicker();}});</script>';
				},
				99
			);
		}

		self::$assets_enqueued = true;
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
