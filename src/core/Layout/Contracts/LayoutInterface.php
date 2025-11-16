<?php
/**
 * Interface for layout components in the WPMoo framework.
 *
 * @package WPMoo\Layout
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Defines the contract for layout components.
 */
interface LayoutInterface {

	/**
	 * Render the layout component.
	 *
	 * @param string $name Input name attribute.
	 * @param mixed  $value Current value.
	 * @return string HTML output.
	 */
	public function render( $name, $value );

	/**
	 * Build the layout configuration.
	 *
	 * @return array Configuration array.
	 */
	public function build();
}
