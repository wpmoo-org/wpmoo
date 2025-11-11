<?php
/**
 * Accordion layout facade.
 *
 * @package WPMoo\Layout
 * @since 0.1.0
 * @link https://wpmoo.org
 * @link https://github.com/wpmoo/wpmoo
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html
 */

namespace WPMoo\Layout;

use WPMoo\Layout\Accordion\Builder as AccordionBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Static helper for creating Accordion builders.
 */
class Accordion {

	/**
	 * Create a new accordion builder.
	 *
	 * @param string $id Accordion identifier.
	 * @return AccordionBuilder
	 */
	public static function make( string $id ): AccordionBuilder {
		return new AccordionBuilder( $id );
	}
}
