<?php
/**
 * Interface for field components in the WPMoo framework.
 *
 * @package WPMoo\Fields
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Defines the contract for field components.
 */
interface FieldInterface {

	/**
	 * Render the field component.
	 *
	 * @param string $name Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string HTML output.
	 */
	public function render( $name, $value );

	/**
	 * Sanitize the field value.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed Sanitized value.
	 */
	public function sanitize( $value );

	/**
	 * Build the field configuration.
	 *
	 * @return array Configuration array.
	 */
	public function build();
}
