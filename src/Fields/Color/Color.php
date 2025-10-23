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
	 * Palette colours derived from the default configuration.
	 *
	 * @var array<int, string>
	 */
	protected $palette = array();

	/**
	 * Whether the palette configuration has been normalised.
	 *
	 * @var bool
	 */
	protected $palette_initialised = false;

	/**
	 * Retrieve the default value, normalising palette configuration when needed.
	 *
	 * @return mixed
	 */
	public function default() {
		$this->ensure_palette_initialised();

		return parent::default();
	}

	/**
	 * Render the field HTML.
	 *
	 * @param string $name  Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string
	 */
	public function render( $name, $value ) {
		$this->maybe_enqueue_assets();

		$this->ensure_palette_initialised();

		$value = null !== $value ? $value : parent::default();
		if ( null !== $value ) {
			$value = $this->esc_attr( (string) $value );
		} else {
			$value = '';
		}

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

		if ( function_exists( 'wp_add_inline_script' ) ) {
			wp_add_inline_script(
				'wp-color-picker',
				'document.addEventListener("DOMContentLoaded",function(){if(window?.jQuery){jQuery(".wpmoo-color-field").wpColorPicker();}});'
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
		$this->ensure_palette_initialised();

		$attributes = $this->args();

		if ( ! empty( $this->palette ) && ! isset( $attributes['data-palette'] ) ) {
			$attributes['data-palette'] = $this->encode_palette( $this->palette );
		}

		return $this->compile_attributes( $attributes );
	}

	/**
	 * Normalise palette data from the default configuration.
	 *
	 * @return void
	 */
	protected function ensure_palette_initialised() {
		if ( $this->palette_initialised ) {
			return;
		}

		$raw_default = $this->default;
		$default     = null;
		$palette     = array();

		if ( is_array( $raw_default ) ) {
			list( $default, $palette ) = $this->parse_palette_default( $raw_default );
		} elseif ( is_string( $raw_default ) && '' !== $raw_default ) {
			$default = $raw_default;
		} elseif ( is_scalar( $raw_default ) && null !== $raw_default ) {
			$default = (string) $raw_default;
		}

		$this->default             = $default;
		$this->palette             = $palette;
		$this->palette_initialised = true;
	}

	/**
	 * Extract palette information from a default configuration array.
	 *
	 * @param array<string|int, mixed> $default_config Default configuration.
	 * @return array{0: string|null, 1: array<int, string>}
	 */
	protected function parse_palette_default( array $default_config ) {
		$default = null;
		$palette = array();

		if ( isset( $default_config['palette'] ) && is_array( $default_config['palette'] ) ) {
			$palette = $this->filter_palette_values( $default_config['palette'] );
		}

		if ( isset( $default_config['value'] ) && is_string( $default_config['value'] ) && '' !== $default_config['value'] ) {
			$default = $default_config['value'];
		}

		if ( empty( $palette ) && $this->is_list( $default_config ) ) {
			$palette = $this->filter_palette_values( $default_config );
		}

		if ( null === $default && ! empty( $palette ) ) {
			$default = $palette[0];
		}

		return array( $default, $palette );
	}

	/**
	 * Determine whether an array is a sequential list.
	 *
	 * @param array<mixed> $items Array to check.
	 * @return bool
	 */
	protected function is_list( array $items ) {
		if ( array() === $items ) {
			return true;
		}

		return array_keys( $items ) === range( 0, count( $items ) - 1 );
	}

	/**
	 * Filter palette values down to non-empty strings.
	 *
	 * @param array<mixed> $values Raw palette values.
	 * @return array<int, string>
	 */
	protected function filter_palette_values( array $values ) {
		$palette = array();

		foreach ( $values as $value ) {
			if ( is_string( $value ) ) {
				$color = trim( $value );
				if ( '' !== $color ) {
					$palette[] = $color;
				}
			}
		}

		return array_values( array_unique( $palette ) );
	}

	/**
	 * Encode the palette into a JSON string for data attributes.
	 *
	 * @param array<int, string> $palette Palette values.
	 * @return string
	 */
	protected function encode_palette( array $palette ) {
		$palette = array_values( $palette );

		if ( function_exists( 'wp_json_encode' ) ) {
			$encoded = wp_json_encode( $palette );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback when wp_json_encode() is unavailable.
			$encoded = json_encode( $palette );
		}

		if ( false === $encoded || null === $encoded ) {
			return '[]';
		}

		return $encoded;
	}
}
